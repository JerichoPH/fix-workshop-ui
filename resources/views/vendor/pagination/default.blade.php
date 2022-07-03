@if ($paginator->hasPages())
    <ul class="pagination no-margin pull-right" role="navigation">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="disabled" aria-disabled="true" aria-label="@lang('pagination.previous')"><span aria-hidden="true">&lsaquo;</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a></li>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a></li>
        @else
            <li class="disabled" aria-disabled="true" aria-label="@lang('pagination.next')"><span aria-hidden="true">&rsaquo;</span></li>
        @endif

        <li style="display: inline-block; width: 200px;">
            <div class="input-group">
                <div class="input-group-addon">跳转到：</div>
                {{--<input style="display: none" class="form-control" autocomplete="off" type="text" id="is_iframe" name="is_iframe" value="{{ request('is_iframe') }}">--}}
                <input type="hidden" name="is_iframe" id="is_iframe" value="{{ request('is_iframe') }}">
                <input
                    class="form-control"
                    autocomplete="off"
                    type="number"
                    min="1"
                    max="{{ $paginator->lastPage() }}"
                    step="1"
                    id="numPage"
                    name="page"
                    value="{{ request('page', 1) }}"
                    onkeydown="if((event.keyCode || event.which) === 13) location.href=`?{!! http_build_query(request()->except('page')) !!}&page=${$('#numPage').val()}`"
                    onfocus="this.select()"
                >
                <div class="input-group-btn">
                    <button
                        type="button"
                        onclick="location.href=`?{!! http_build_query(request()->except('page')) !!}&page=${$('#numPage').val()}`"
                        class="btn btn-default"
                    >
                        确定
                    </button>
                </div>
            </div>
        </li>
    </ul>
@endif
