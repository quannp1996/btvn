@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <form action="{{ route('listpurchase') }}" method="get">
                    <input name="pur_id" value="{{ @request()->pur_id }}" placeholder="id order">
                    <input name="user_id" value="{{ @request()->user_id }}"  placeholder="id user">
                    <button type="submit">Search product</button>
                </form>
                <br/>
                <table>
                    <thead>
                        <tr>
                            <th width="55">ID</th>
                            <th width="100">Username</th>
                            <th width="100">Address</th>
                            <th width="100">Name product</th>
                            <th width="100">Quantity</th>
                            <th width="100">Price</th>
                            <th width="200">Seller</th>
                            <th width="200">Date</th>
                            <th width="100">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($data))
                            @foreach($data as $item)
                                <td>
                                    {{ @$item->pur_id}}
                                </td>
                                <td>
                                    {{ @$item->name}}
                                </td>
                                <td>
                                    {{ @$item->user_address}}
                                </td>
                                <td>
                                    {{ @$products[$item->item_id]['item_name']}}
                                </td>
                                <td>
                                    {{ @$item->quantity}}
                                </td>
                                <td>
                                    {{ @$item->price}}
                                </td>
                                <td>
                                    {{ @$item->seller_ip}}
                                </td>
                                <td>
                                    {{ @$item->date}}
                                </td>
                                <td>
                                   <button type="button" data-id="{{ @$item->pur_id}}" class="delete">Delete</button>
                                </td>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script>
    $(window).bind('load', function () {
        $('.delete').click(function () {
            console.log($(this).attr('data-id'));
            $.ajax({
                url:  '{{ route('delete') }}',
                type:'POST',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'id':$(this).attr('data-id')
                },
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
