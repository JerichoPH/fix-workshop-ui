@if (session('danger',null)!==null)
    <div class="alert alert-danger">
        <span>{!! session('danger') !!}</span>
    </div>
@endif
