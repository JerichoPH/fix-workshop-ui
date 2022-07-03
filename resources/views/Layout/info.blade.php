@if (session('info',null)!==null)
    <div class="alert alert-info">
        <span>{!! session('info') !!}</span>
    </div>
@endif
