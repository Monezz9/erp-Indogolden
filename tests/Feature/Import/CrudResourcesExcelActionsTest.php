<?php

namespace Tests\Feature\Import;

use App\Filament\Concerns\HasResourceExcelActions;
use Filament\Resources\Pages\PageRegistration;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrudResourcesExcelActionsTest extends TestCase
{
    public function test_all_crud_resources_include_excel_header_actions_on_list_page(): void
    {
        $resourceFiles = glob(app_path('Filament/Resources/*/*Resource.php')) ?: [];

        $checked = 0;

        foreach ($resourceFiles as $file) {
            $resourceClass = $this->resourceClassFromFile($file);

            if (! class_exists($resourceClass)) {
                continue;
            }

            $pages = $resourceClass::getPages();

            if (! isset($pages['index'], $pages['create'], $pages['edit'])) {
                continue;
            }

            $checked++;

            $indexPage = $pages['index'];
            $this->assertInstanceOf(PageRegistration::class, $indexPage);

            $listPageClass = $indexPage->getPage();
            $usedTraits = class_uses_recursive($listPageClass);

            $this->assertContains(
                HasResourceExcelActions::class,
                $usedTraits,
                sprintf('List page %s belum memakai HasResourceExcelActions.', $listPageClass),
            );
        }

        $this->assertGreaterThan(0, $checked, 'Tidak ada resource CRUD yang tervalidasi.');
    }

    protected function resourceClassFromFile(string $filePath): string
    {
        $relativePath = Str::after(str_replace('/', DIRECTORY_SEPARATOR, $filePath), app_path().DIRECTORY_SEPARATOR);
        $trimmed = Str::replaceLast('.php', '', $relativePath);

        return 'App\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $trimmed);
    }
}
