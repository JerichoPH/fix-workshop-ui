<select
    id="{{ $selectId }}"
    name="{{ $selectName }}"
    class="form-control select2"
    style="width:100%;">
</select>
<script>
    window.onload = function(){
        $obj = $('#{{ $selectId }}');
        $.each({{ $data  }},function(k,v){
            let html = '';
            $.each(initData, function (k, v) {
                html += `<option value="${k}">${v['name']}</option>`;
            });
            $obj.html(html);
        });
    };

</script>
