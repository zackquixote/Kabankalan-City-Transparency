<?php

namespace Tests\Database;

use App\Models\ProjectModel;
use App\Models\ProjectAttachmentModel;
use App\Services\AttachmentService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\ControllerTestTrait;
use Tests\Support\TestUploadedFile;

class AttachmentControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use ControllerTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $seed        = \App\Database\Seeds\DatabaseSeeder::class;
    protected $namespace   = 'App';

    private ProjectModel           $projectModel;
    private ProjectAttachmentModel $attachmentModel;
    private AttachmentService      $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectModel    = new ProjectModel();
        $this->attachmentModel = new ProjectAttachmentModel();
        $this->service         = new AttachmentService();
    }

    public function testUploadAttachmentUnauthorizedRedirects()
    {
        // No user in session
        session()->remove('user_id');

        // Note: AdminAuthFilter would typically block this at route/group level.
        // But running the controller method directly will execute AdminBaseController authz checks.
        $result = $this->controller(\App\Controllers\Admin\AttachmentManager::class)
                       ->execute('upload', 1);

        $this->assertEquals(404, $result->response()->getStatusCode());
    }

    public function testUploadAttachmentAuthorizedSuccess()
    {
        // 1. Log in as super admin
        session()->set([
            'user_id'    => 1,
            'user_email' => 'admin@kabankalan.gov.ph',
            'user_role'  => 'super_admin',
            'logged_in'  => true,
        ]);

        // Get a test project
        $project = $this->projectModel->first();
        $projectId = (int)$project['id'];

        // Create a mocked file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_ctrl_img');
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tempFile, $pngContent);

        $mockFile = new TestUploadedFile($tempFile, 'chart.png', 'image/png', strlen($pngContent), UPLOAD_ERR_OK);

        // Inject file into the Request object using reflection
        $request = service('request');
        $ref = new \ReflectionClass($request);
        $prop = $ref->getProperty('files');
        $prop->setAccessible(true);
        
        $collection = new \CodeIgniter\HTTP\Files\FileCollection();
        $refColl = new \ReflectionClass($collection);
        $propColl = $refColl->getProperty('files');
        $propColl->setAccessible(true);
        $propColl->setValue($collection, [
            'attachment' => $mockFile
        ]);
        $prop->setValue($request, $collection);

        // Execute upload controller method
        $result = $this->controller(\App\Controllers\Admin\AttachmentManager::class)
                       ->execute('upload', $projectId);

        // Assert redirect to the project detail page
        $this->assertTrue($result->isRedirect());
        $this->assertStringContainsString('/admin/projects/' . $projectId, $result->getRedirectUrl());

        // Assert record exists in database
        $attachments = $this->attachmentModel->forProject($projectId);
        $this->assertNotEmpty($attachments);
        $uploaded = $attachments[0];
        $this->assertEquals('chart.png', $uploaded['original_filename']);

        // Clean up stored file
        $this->service->delete((int)$uploaded['id']);
    }

    public function testDeleteAttachmentSuccess()
    {
        // 1. Log in as super admin
        session()->set([
            'user_id'    => 1,
            'user_email' => 'admin@kabankalan.gov.ph',
            'user_role'  => 'super_admin',
            'logged_in'  => true,
        ]);

        $project = $this->projectModel->first();
        $projectId = (int)$project['id'];

        // Create an attachment first
        $tempFile = tempnam(sys_get_temp_dir(), 'test_del_img');
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tempFile, $pngContent);
        $mockFile = new TestUploadedFile($tempFile, 'todelete.png', 'image/png', strlen($pngContent), UPLOAD_ERR_OK);

        $storeRes = $this->service->store($mockFile, $projectId, 1, 'Temporary Photo');
        $this->assertTrue($storeRes['ok']);
        $attachmentId = (int)$storeRes['attachment']['id'];

        // Execute delete method on controller
        $result = $this->controller(\App\Controllers\Admin\AttachmentManager::class)
                       ->execute('delete', $projectId, $attachmentId);

        $this->assertTrue($result->isRedirect());
        $this->assertNull($this->attachmentModel->find($attachmentId));
    }

    public function testPublicDownloadPublishedProjectAttachment()
    {
        // Make sure a project is published
        $project = $this->projectModel->first();
        $projectId = (int)$project['id'];
        $this->projectModel->update($projectId, ['status' => 'published']);

        // Create an attachment
        $tempFile = tempnam(sys_get_temp_dir(), 'test_pub_img');
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tempFile, $pngContent);
        $mockFile = new TestUploadedFile($tempFile, 'published.png', 'image/png', strlen($pngContent), UPLOAD_ERR_OK);

        $storeRes = $this->service->store($mockFile, $projectId, 1, 'Published Image');
        $this->assertTrue($storeRes['ok']);
        $attachmentId = (int)$storeRes['attachment']['id'];

        // Execute download on public controller
        $result = $this->controller(\App\Controllers\ProjectAttachment::class)
                       ->execute('download', $attachmentId);

        $this->assertTrue($result->isOK());
        $response = $result->response();
        $this->assertEquals('image/png', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('inline; filename="published.png"', $response->getHeaderLine('Content-Disposition'));
        $this->assertEquals($pngContent, $response->getBody());

        // Clean up
        $this->service->delete($attachmentId);
    }

    public function testPublicDownloadDraftProjectAttachment404s()
    {
        // Make sure project is in draft status
        $project = $this->projectModel->first();
        $projectId = (int)$project['id'];
        $this->projectModel->update($projectId, ['status' => 'draft']);

        // Create an attachment
        $tempFile = tempnam(sys_get_temp_dir(), 'test_draft_img');
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tempFile, $pngContent);
        $mockFile = new TestUploadedFile($tempFile, 'draft.png', 'image/png', strlen($pngContent), UPLOAD_ERR_OK);

        $storeRes = $this->service->store($mockFile, $projectId, 1, 'Draft Image');
        $this->assertTrue($storeRes['ok']);
        $attachmentId = (int)$storeRes['attachment']['id'];

        // Execute download on public controller - should 404
        $result = $this->controller(\App\Controllers\ProjectAttachment::class)
                       ->execute('download', $attachmentId);

        $this->assertEquals(404, $result->response()->getStatusCode());
        $this->service->delete($attachmentId);
    }
}
