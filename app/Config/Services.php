<?php

namespace Config;

use App\Services\AipRegistryService;
use App\Services\AuthorizationService;
use App\Services\BudgetSummaryService;
use App\Services\CitizenHomeService;
use App\Services\FeedbackService;
use App\Services\ProjectDetailService;
use App\Services\ProjectFilterService;
use App\Services\VisionCatalogService;
use App\Services\VersioningService;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function authorization(bool $getShared = true): AuthorizationService
    {
        if ($getShared) {
            return static::getSharedInstance('authorization');
        }

        return new AuthorizationService();
    }

    public static function projectFilter(bool $getShared = true): ProjectFilterService
    {
        if ($getShared) {
            return static::getSharedInstance('projectFilter');
        }

        return new ProjectFilterService();
    }

    public static function budgetSummary(bool $getShared = true): BudgetSummaryService
    {
        if ($getShared) {
            return static::getSharedInstance('budgetSummary');
        }

        return new BudgetSummaryService();
    }

    public static function versioning(bool $getShared = true): VersioningService
    {
        if ($getShared) {
            return static::getSharedInstance('versioning');
        }

        return new VersioningService();
    }

    public static function feedback(bool $getShared = true): FeedbackService
    {
        if ($getShared) {
            return static::getSharedInstance('feedback');
        }

        return new FeedbackService();
    }

    public static function citizenHome(bool $getShared = true): CitizenHomeService
    {
        if ($getShared) {
            return static::getSharedInstance('citizenHome');
        }

        return new CitizenHomeService();
    }

    public static function visionCatalog(bool $getShared = true): VisionCatalogService
    {
        if ($getShared) {
            return static::getSharedInstance('visionCatalog');
        }

        return new VisionCatalogService();
    }

    public static function aipRegistry(bool $getShared = true): AipRegistryService
    {
        if ($getShared) {
            return static::getSharedInstance('aipRegistry');
        }

        return new AipRegistryService();
    }

    public static function projectDetail(bool $getShared = true): ProjectDetailService
    {
        if ($getShared) {
            return static::getSharedInstance('projectDetail');
        }

        return new ProjectDetailService();
    }
}
