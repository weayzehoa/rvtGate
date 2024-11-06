<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Arr;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Traits\ApiResponser;
use Throwable;
use Session;

class Handler extends ExceptionHandler
{
    use ApiResponser;

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
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        $this->renderable(function (Throwable $e) {
            //判斷 domain 是否為 api domain
            if(request()->getHost() == env('API_DOMAIN')){
                return $this->handleException($e);
            }else{
                if ($e->getPrevious() instanceof TokenMismatchException) { //CSRF Token Missing.
                    Session::put('error',$e->getMessage());
                    return redirect()->route('gate.login');
                }
            }
        });
    }

    public function handleException(Throwable $e){
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->appCodeResponse('Error', 999, 'The specified method for the request is invalid', 405);
        }
        if ($e instanceof NotFoundHttpException) {
            return $this->appCodeResponse('Error', 999, 'The specified URL cannot be found', 404);
        }
        if ($e instanceof HttpException) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
        return $this->appCodeResponse('Error', 999, 'Unexpected Exception. Try later', 500);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return $this->appCodeResponse('Error', 999, 'Unauthenticated', 401);
        }

        $guard = Arr::get($exception->guards(), 0);

        //用來判斷是 User or Admin or Vendor 來的使用者並轉到正確的登入頁面
        switch ($guard) {
            case 'admin':
            $login='admin.login';
            break;

            case 'vendor':
            $login='vendor.login';
            break;

            case 'gate':
            $login='gate.login';
            break;

            default:
            $login='login';
            break;
        }

        return redirect()->guest(route($login));
    }
}
