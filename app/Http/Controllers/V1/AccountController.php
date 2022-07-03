<?php

namespace App\Http\Controllers\V1;

use App\Facades\DingResponseFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ForgetPasswordRequest;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Requests\V1\RegisterRequest;
use App\Http\Requests\V1\UpdatePasswordRequest;
use App\Model\Account;
use App\Transformers\AccountTransformer;
use Dingo\Api\Routing\Helpers;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Jericho\HttpResponseHelper;
use Jericho\JwtHelper;
use Jericho\Redis\Hashs;
use Jericho\Redis\Strings;
use Jericho\ValidateHelper;

class AccountController extends Controller
{
    use Helpers;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accounts = Account::with(["status", "organization"])->orderBy('id', 'desc')->paginate();
        return $this->response->paginator($accounts, new AccountTransformer);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $openId
     * @return \Illuminate\Http\Response
     */
    public function show($openId)
    {
        try {
            $account = Account::with(['status', 'organization', 'roles'])->where('open_id', $openId)->firstOrFail();
            return $this->response->item($account, new AccountTransformer);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound($exception->getMessage());
        } catch (\Exception $exception) {
            $this->response->errorInternal($exception->getMessage());
        }
    }

    public function profile()
    {
        try {
            $token = JwtHelper::INS()->parse(request()->header('token'));
            $account = Account::with(['status', 'organization', 'roles'])->where('open_id', $token->payload->open_id)->firstOrFail();
            return $this->response->item($account, new AccountTransformer);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound($exception->getMessage());
        } catch (\Exception $exception) {
            $this->response->errorInternal($exception->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $openId
     * @return \Illuminate\Http\Response
     */
    public function edit($openId)
    {
        try {
            $token = JwtHelper::INS()->parse(request()->header('token'));
            if ($token->payload->open_id == $openId) {
                $account = Account::where('open_id', $token->payload->open_id)->firstOrFail();
            } else {
                $account = Account::where('open_id', $openId)->firstOrFail();
            }
            return $this->response->item($account, new AccountTransformer);
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound($exception->getMessage());
        } catch (\Exception $exception) {
            $this->response->errorInternal($exception->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {

            $token = JwtHelper::INS()->parse(request()->header('token'));
            $account = Account::where('open_id', $token->payload->open_id)->firstOrFail();
            $req = $request->all();
            unset($req['password']);
            $account->fill($request->all());
            $account->saveOrFail();

            return $this->response->accepted();
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound($exception->getMessage());
        } catch (\Exception $exception) {
            $this->response->errorInternal($exception->getMessage());
        }
    }

    public function forget(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new ForgetPasswordRequest);
            if ($v !== true) $this->response->errorForbidden($v);

            switch ($request->get('type')) {
                case 'email':
                    $account = Account::where('account', $request->get('account'))->where('email_code_exp', '>', date('Y-m-d H:i:s'))->first();
                    if (!$account) $this->response->errorNotFound('账号不存在或验证码过期');
                    $code = $account->email_code;
            }
            if ($request->get('code') != $code) $this->response->errorForbidden('验证码错误');
            $account->password = bcrypt($request->get('password'));
            $account->saveOrFail();
            return $this->response->accepted();
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound($exception->getMessage());
        } catch (\Exception $exception) {
            $this->response->errorInternal($exception->getMessage());
        }
    }

    public function password(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new UpdatePasswordRequest);
            if ($v !== true) $this->response->errorForbidden($v);

            $token = JwtHelper::INS()->parse($request->header('token'));
            $account = Account::where('open_id', $token->payload->open_id)->firstOrFail();
            if (!Hash::check($request->get('password'), $account->password)) $this->response->errorForbidden('账号或密码不匹配');
            $account->password = bcrypt($request->get('new_password'));
            $account->saveOrFail();

            # 删除对应的缓存
            Strings::ins()->setOne('account:' . $account->account);

            return $this->response->accepted();
        } catch (ModelNotFoundException $exception) {
            $this->response->errorNotFound($exception->getMessage());
        } catch (\Exception $exception) {
            $this->response->errorInternal($exception->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * 登录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            # 表单验证
            $v = ValidateHelper::firstErrorByRequest($request, new LoginRequest);
            if ($v !== true) return HttpResponseHelper::errorValidate($v);

            # 验证密码
            $account = Account::where('account', $request->get('account'))->firstOrFail();
            if (!Hash::check($request->get('password'), $account->password)) return HttpResponseHelper::errorUnauthorized('账号或密码不匹配');
            $_SESSION["account"]=$request->get("account");
            $_SESSION["password"]=$request->get("password");
            # 生成jwt
            $jwt = JwtHelper::INS()->setExp()->create($account->account, $account->open_id, $account->nickanme);

            return DingResponseFacade::data($jwt);
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('用户不存在');
        } catch (\Exception $exception) {
            $eMsg = $exception->getMessage();
            $eCode = $exception->getCode();
            $eLine = $exception->getLine();
            $eFile = $exception->getFile();
            return HttpResponseHelper::error(env('APP_DEBUG') ?
                "{$eMsg}<br>\r\n{$eFile}<br>\r\n{$eLine}" :
                "意外错误");
        }
    }

    final public function jwt()
    {
        return HttpResponseHelper::data((array)session('token'));
    }


    /**
     * 注册
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * @throws \Throwable
     */
    public function register(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new RegisterRequest);
            if ($v !== true) $this->response->errorForbidden($v);

            $account = new Account;
            $req = $request->all();
            $req['open_id'] = md5(Hashs::ins()->setIncr('count', 'account') . time() . $req['account']);
            $req['password'] = bcrypt($req['password']);
            $account->fill($req);
            $account->saveOrFail();
            return $this->response->created();
        } catch (\Exception $exception) {
            $this->response->errorInternal($exception->getMessage());
        }
    }
}
