@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
{{--            @if ($errors->any())--}}
{{--                <div class="alert alert-danger">--}}
{{--                    <ul>--}}
{{--                        @foreach ($errors->all() as $error)--}}
{{--                            <li>Error: {{ $error }}</li>--}}
{{--                        @endforeach--}}
{{--                    </ul>--}}
{{--                </div>--}}
{{--            @endif--}}
{{--            @if(session()->has('susscess'))--}}
{{--                <div class="alert alert-success">--}}
{{--                    Susscess :{{ session()->get('susscess') }}--}}
{{--                </div>--}}
{{--                <div class="alert alert-success">--}}
{{--                    pur_id: {{ session()->get('pur_id') }}--}}
{{--                </div>--}}
{{--            @endif--}}
            <form action="{{ route('home') }}" method="get">
                <input name="name" value="{{ @request()->name }}">
                <button type="submit">Search product</button>
            </form>
            <br/>
            <form action="{{ route('purchase') }}" method="post" id="save-purchase">
            @csrf
            <h4> List product</h4>

            @if(!empty($data) && $data->count()>0)
                @foreach($data as $item)
                    <input type="radio" id="product{{ @$item['item_id'] }}" name="product" value="{{ @$item['item_id'] }}">
                    <label for="product{{ @$item['item_id'] }}">{{ @$item['item_name'] }} - {{ @$item['price_of_unit']?$item['price_of_unit'].'$':'' }}</label><br>
                @endforeach
            @else
                <div>No products found</div>
            @endif

            <br/>
            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" name="quantity" value="" required min="1">
            <br/>
            <br/>
            <button type="button" id="purchase">Purchase</button>
            </form>

        </div>
    </div>
</div>
@endsection
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script>
    $(window).bind('load', function () {
        $('#purchase').click(function () {
            var data = $('#save-purchase').serializeArray();
            $.ajax({
                url:  '{{ route('purchase') }}',
                type:'POST',
                data: data,
                dataType: 'json',
                success: function(json, textStatus, xhr) {
                    if (xhr.status != 200) {
                        alert('status:'+xhr.status+' message:'+ json.msg);
                    } else {
                        alert('status:'+xhr.status+' message:'+ json.msg+' pur_id:'+ json.pur_id);
                        location.reload();
                    }
                },
                error: function (xhr, statusText, err) {
                    if(xhr.status != 500){
                        var msg = '';
                        var validate = JSON.parse(xhr.responseText);
                        for (const [key, error_msg] of Object.entries(validate.msg)){
                            msg+= ' '+error_msg;
                        }
                        alert("Error:" + xhr.status+' message:'+ msg);
                    }else{
                        alert("Error:" + xhr.status+' message:'+ err);
                    }
                }
            });
        })
    });
</script>
