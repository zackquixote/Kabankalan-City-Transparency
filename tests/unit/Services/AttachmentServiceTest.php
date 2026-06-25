<?php

namespace Tests\Unit\Services;

use App\Models\ProjectAttachmentModel;
use App\Models\ProjectModel;
use App\Services\AttachmentService;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

use Tests\Support\TestUploadedFile;

class AttachmentServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $seed        = \App\Database\Seeds\DatabaseSeeder::class;
    protected $namespace   = 'App';

    private AttachmentService $service;
    private ProjectModel      $projectModel;
    private int               $projectId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectModel = new ProjectModel();
        $this->service      = new AttachmentService();

        // Let's grab/create a dummy project to attach files to
        $project = $this->projectModel->first();
        if ($project === null) {
            $this->projectId = $this->projectModel->insert([
                'project_code' => 'TEST-101',
                'title'        => 'Test Project for Attachments',
                'office_id'    => 1,
                'vision_id'    => 1,
                'budget_cycle_stage_id' => 1,
                'fiscal_year'  => 2026,
                'allocated_amount' => 100000.00,
                'status'       => 'draft',
                'created_by'   => 1,
            ], true);
        } else {
            $this->projectId = (int)$project['id'];
        }
    }

    public function testStoreValidImageAttachment()
    {
        // Create a dummy image file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_img');
        // A minimal valid 1x1 transparent PNG:
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tempFile, $pngContent);

        $file = new TestUploadedFile($tempFile, 'site-photo.png', 'image/png', strlen($pngContent), UPLOAD_ERR_OK);

        $result = $this->service->store($file, $this->projectId, 1, 'Site Photo Before Construction');

        $this->assertTrue($result['ok']);
        $this->assertArrayHasKey('attachment', $result);
        $attachment = $result['attachment'];
        $this->assertEquals('site-photo.png', $attachment['original_filename']);
        $this->assertEquals('image/png', $attachment['mime_type']);
        $this->assertEquals('Site Photo Before Construction', $attachment['label']);

        // Check file exists on disk
        $diskPath = $this->service->resolveDiskPath($attachment['stored_filename']);
        $this->assertNotNull($diskPath);
        $this->assertFileExists($diskPath);
        $this->assertEquals($pngContent, file_get_contents($diskPath));

        // Clean up
        $this->service->delete((int)$attachment['id']);
        $this->assertFileDoesNotExist($diskPath);
    }

    public function testStoreInvalidMimeTypeRejected()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_php');
        $phpContent = '<?php echo "evil shell"; ?>';
        file_put_contents($tempFile, $phpContent);

        $file = new TestUploadedFile($tempFile, 'shell.php', 'text/x-php', strlen($phpContent), UPLOAD_ERR_OK);

        $result = $this->service->store($file, $this->projectId, 1, 'Malicious File');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('File type not allowed', $result['error']);

        @unlink($tempFile);
    }

    public function testStoreFileSizeExceedsLimit()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_large');
        $largeSize = AttachmentService::MAX_BYTES + 1024;
        file_put_contents($tempFile, 'dummy content');

        $file = new TestUploadedFile($tempFile, 'large-file.pdf', 'application/pdf', $largeSize, UPLOAD_ERR_OK);

        $result = $this->service->store($file, $this->projectId, 1, 'Too large');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('File exceeds the maximum allowed size', $result['error']);

        @unlink($tempFile);
    }
}
