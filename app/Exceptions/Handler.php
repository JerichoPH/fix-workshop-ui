<?php

namespace App\Exceptions;

use App\Facades\JsonResponseFacade;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];
    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        "password",
        "password_confirmation",
    ];
    private $__code = 0;

    /**
     * Report or log an exception.
     *
     * @param Exception $e
     * @return void
     */
    public function report(Exception $e)
    {
        // $this->__code = time() . str_pad(rand(0, 9999), 2, "0", 0);
        $this->__code = Str::uuid();

        // parent::report($e);

        Log::error($e->getMessage(), [
            "request" => request()->all(),
            "code" => $this->__code,
            "trace" => $e->getTraceAsString(),
        ]);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Exception $e
     * @return Response
     */
    /**
     * @param Request $request
     * @param Exception $e
     * @return Application|JsonResponse|RedirectResponse|Response|Redirector|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e)
    {
        $e_msg = $e->getMessage();
        $msg = "错误：{$e_msg}。";
        if (env("APP_DEBUG")) $msg .= "（错误代码：{$this->__code}）";

        if (env("APP_DEBUG")) {
            if (!$request->ajax()) {
                dd([
                    "exception_type" => get_class($e),
                    "message" => $e->getMessage(),
                    "file" => $e->getFile(),
                    "line" => $e->getLine(),
                    "trace" => $e->getTrace(),
                ]);
            }
        }

        if ($e instanceof UnAuthorizationException) {
            return $request->ajax()
                ? JsonResponseFacade::errorUnauthorized($msg)
                : back()->withInput()->with("danger", $msg);
        }

        if ($e instanceof EmptyException) {
            return $request->ajax()
                ? JsonResponseFacade::errorEmpty($msg)
                : back()->withInput()->with("danger", $msg);
        }

        if ($e instanceof ForbiddenException) {
            return $request->ajax()
                ? JsonResponseFacade::errorForbidden($msg)
                : back()->withInput()->with("danger", $msg);
        }

        if ($e instanceof UnLoginException) {
            return $request->ajax()
                ? JsonResponseFacade::errorUnLogin($msg)
                : redirect("/login", $msg);
        }

        if ($e instanceof UnOwnerException) {
            return $request->ajax()
                ? JsonResponseFacade::errorUnauthorized($msg)
                : back()->withInput()->with("danger", $msg);
        }

        if ($e instanceof ValidateException) {
            return $request->ajax()
                ? JsonResponseFacade::errorValidate($msg)
                : back()->withInput()->with("danger", $msg);
        }

        if ($e instanceof ModelNotFoundException) {
            return $request->ajax()
                ? JsonResponseFacade::errorEmpty("错误：资源不存在。错误代码：{$this->__code}")
                : back()->withInput()->with("danger", "错误：资源不存在。错误代码：{$this->__code}");
        }

        if ($e instanceof NotFoundHttpException) {
            return $request->ajax()
                ? JsonResponseFacade::errorEmpty("错误：路由不存在。错误代码：{$this->__code}")
                : back()->withInput()->with("danger", "错误：资源不存在。错误代码：{$this->__code}");
        }

        if ($e instanceof ExcelInException) {
            return $request->ajax()
                ? JsonResponseFacade::errorForbidden($msg)
                : back()->with("danger", $msg);
        }

        if ($e instanceof Exception) {
            return $request->ajax()
                ? JsonResponseFacade::errorException($e)
                : back()->withInput()->with("danger", "意外错误。错误代码：{$this->__code}");
        }

        return parent::render($request, $e);
    }
}
