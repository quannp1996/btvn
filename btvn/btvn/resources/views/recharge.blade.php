@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <ul>
                   <li> User name: {{ \Illuminate\Support\Facades\Auth::user()->name }} </li>
                   <li> Balance: {{ \Illuminate\Support\Facades\Auth::user()->balance }} </li>
                </ul>
                <br/>
                <form action="{{ route('saverecharge') }}" method="post" id="save-recharge">
                    @csrf
                    <label for="card_num">Card number</label>
                    <input type="number" id="card_num" name="card_num" value="">
                    <br/>
                    <label for="pin">Pin</label>
                    <input type="password" id="pin" name="pin" value="">
                    <br/>
                    <label for="amount">Amount of money</label>
                    <input type="number" id="amount" name="amount" value="" required min="0">
                    <br/>
                    <br/>
                    <button type="button" id="recharge">Recharge</button>
                </form>
            </div>
        </div>
    </div>
@endsection
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script>
    $(window).bind('load', function () {
        $('#recharge').click(function () {
            var data = $('#save-recharge').serializeArray();
            $.ajax({
                url:  '{{ route('saverecharge') }}',
                type:'POST',
                data: data,
                dataType: 'json',
                success: function(json, textStatus, xhr) {
                    if (xhr.status != 200) {
                        alert('status:'+xhr.status+' message:'+ json.msg);
                    } else {
                        alert('status:'+xhr.status+' message:'+ json.msg);
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
