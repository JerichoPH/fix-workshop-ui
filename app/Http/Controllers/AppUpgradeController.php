<?php

namespace App\Http\Controllers;

use App\Exceptions\ForbiddenException;
use App\Facades\JsonResponseFacade;
use App\Model\AppUpgrade;
use App\Model\File;
use App\Model\PivotAppUpgradeAndAccessory;
use App\Validations\Web\AppUpgradeStoreValidation;
use App\Validations\Web\AppUpgradeUpdateValidation;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AppUpgradeController extends Controller
{
    /**
     * 列表页面
     * @return Factory|Application|View
     */
    final public function Index()
    {
        $appUpgrades = (new AppUpgrade())->ReadMany()->orderByDesc("id");

        if (request()->ajax()) {
            return JsonResponseFacade::dict(["app_upgrades" => $appUpgrades->get(),]);
        } else {
            return view("AppUpgrade.index");
        }
    }

    /**
     * 详情页面
     * @param string $uniqueCode
     * @return Factory|Application|View
     */
    final public function Show(string $uniqueCode)
    {
        $appUpgrade = (new AppUpgrade())->ReadOneByUniqueCode($uniqueCode);

        if (request()->ajax()) {
            return JsonResponseFacade::dict(["app_upgrade" => $appUpgrade->first()]);
        } else {
            return view("AppUpgrade.index");
        }
    }

    /**
     * 新建页面
     * @return Factory|Application|View
     */
    final public function Create()
    {
        return view("AppUpgrade.create");
    }

    /**
     * 新建
     * @param Request $request
     * @return RedirectResponse
     */
    final public function Store(Request $request): RedirectResponse
    {
        $validation = (new AppUpgradeStoreValidation($request));
        $v = $validation
            ->check()
            ->after(function ($validator) use ($request) {
                if (
                AppUpgrade::with([])
                    ->where("target", $request->get("target"))
                    ->where("version", $request->get("version"))
                    ->exists()
                ) {
                    $validator->errors()->add("version", "{$request->get("target")}下已经有版本：{$request->get("version")}了");
                }
            });
        if ($v->fails()) return back()->with("danger", $v->errors()->first());

        $validated = $validation->validated();

        $appUpgrade = AppUpgrade::with([])
            ->create([
                "unique_code" => Str::uuid(),
                "version" => $validated->get("version") ?: "",
                "target" => $validated->get("target") ?: "",
                "description" => $validated->get("description") ?: "",
                "operating_steps" => $validated->get("operating_steps") ?: "",
                "upgrade_reports" => $validated->get("upgrade_reports") ?: "",
            ]);

        if ($request->file("accessories")) {
            // 检查文件格式
            collect($request->file("accessories"))->each(function (UploadedFile $accessory) {
                if (
                !in_array($accessory->getClientOriginalExtension(),
                    ["zip", "pdf", "doc", "docx", "xls", "xlsx", "txt", "sql", "tar", "tar.gz", "sql.gz", "gz",])
                ) {
                    throw new ForbiddenException("上传文件格式错误");
                }
            });

            $saveFiles = File::storeBatch(
                $request->file("accessories"),
                "appUpgradeAccessory",
                "public/appUpgradeAccessories",
                "appUpgradeAccessories",
                "FILE"
            );

            if (!empty($saveFiles)) {
                collect($saveFiles)->each(function ($saveFile) use ($appUpgrade) {
                    PivotAppUpgradeAndAccessory::with([])
                        ->create([
                            "app_upgrade_id" => $appUpgrade->id,
                            "file_id" => $saveFile->id,
                        ]);
                });
            }
        }

        return back()->with("success", "新建成功");
    }

    /**
     * 编辑页面
     * @param string $uniqueCode
     * @return Factory|Application|View
     */
    final public function Edit(string $uniqueCode)
    {
        $appUpgrade = AppUpgrade::with([])->where("unique_code", $uniqueCode)->firstOrFail();
        return view("AppUpgrade.edit", ["appUpgrade" => $appUpgrade,]);
    }

    /**
     * 编辑
     * @throws Throwable
     */
    final public function Update(Request $request, string $uniqueCode): RedirectResponse
    {
        $validation = (new AppUpgradeUpdateValidation($request));
        $v = $validation
            ->check()
            ->after(function ($validator) use ($request, $uniqueCode) {
                if (
                AppUpgrade::with([])
                    ->where("target", $request->get("target"))
                    ->where("version", $request->get("version"))
                    ->where("unique_code", "<>", $uniqueCode)
                    ->exists()
                ) {
                    $validator->errors()->add("version", "{$request->get("target")}下已经有版本：{$request->get("version")}了");
                }
            });
        if ($v->fails()) return back()->with("danger", $v->errors()->first());

        $validated = $validation->validated();

        $appUpgrade = AppUpgrade::with([])->where("unique_code", $uniqueCode)->firstOrFail();
        $appUpgrade
            ->fill([
                "version" => $validated->get("version") ?: "",
                "target" => $validated->get("target") ?: "",
                "description" => $validated->get("description") ?: "",
                "operating_steps" => $validated->get("operating_steps") ?: "",
                "upgrade_reports" => $validated->get("upgrade_reports") ?: "",
            ])
            ->saveOrFail();

        if ($request->file("accessories")) {
            // 检查文件格式
            collect($request->file("accessories"))->each(function (UploadedFile $accessory) {
                if (
                !in_array($accessory->getClientOriginalExtension(),
                    ["zip", "pdf", "doc", "docx", "xls", "xlsx", "txt", "sql", "tar", "tar.gz", "sql.gz", "gz",])
                ) {
                    throw new ForbiddenException("上传文件格式错误");
                }
            });

            $saveFiles = File::storeBatch(
                $request->file("accessories"),
                "appUpgradeAccessory",
                "public/appUpgradeAccessories",
                "appUpgradeAccessories",
                "FILE"
            );

            if (!empty($saveFiles)) {
                collect($saveFiles)->each(function ($saveFile) use ($appUpgrade) {
                    PivotAppUpgradeAndAccessory::with([])
                        ->create([
                            "app_upgrade_id" => $appUpgrade->id,
                            "file_id" => $saveFile->id,
                        ]);
                });
            }
        }

        return back()->with("success", "编辑成功");
    }

    /**
     * 删除
     * @param string $uniqueCode
     * @throws Exception
     */
    final public function Destroy(string $uniqueCode)
    {
        $appUpgrade = AppUpgrade::with([])->where("unique_code", $uniqueCode)->firstOrFail();
        $appUpgrade->delete();
    }

    /**
     * 删除附件
     * @param string $identityCode
     * @throws Exception
     */
    final public function DeleteAccessory(string $identityCode)
    {
        $accessory = File::with([])->where("identity_code", $identityCode)->firstOrFail();
        if ($accessory->out_put->is_exist) {
            unlink($accessory->out_put->save_path);
        }

        PivotAppUpgradeAndAccessory::with([])->where("file_id", $accessory->id)->delete();

        $accessory->delete();

        return JsonResponseFacade::deleted();
    }
}
