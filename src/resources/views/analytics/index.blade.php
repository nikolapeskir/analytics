<html>
    <head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <body>
        @if(count($accounts) > 0)
            <select id="accounts">
                <option value="">Choose</option>
                @foreach($accounts as $account)
                    <option value="{{ $account['id'] }}">{{ $account['name'] }}</option>
                @endforeach
            </select>
            <select id="properties" style="display: none;">
            </select>
            <select id="views" style="display: none;">
            </select>
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
            <script type="text/javascript">
                let accountId = '',
                    propertyId = '',
                    viewId = '',
                    foreignId = '',
                    propertiesDrop = $('#properties'),
                    viewsDrop = $('#views');

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                function getAccountData() {
                    let urlPath = (accountId != '' && propertyId != '')
                        ? 'views'
                        : 'properties';

                    jQuery.ajax({
                        url: 'analytics/' + urlPath,
                        type: 'POST',
                        // dataType: 'JSON',
                        data: {
                            'accountId': accountId,
                            'propertyId': propertyId,
                        },
                        success: function(data) {
                            populateDropdown(data, urlPath);
                        },
                        error: function(error) {
                            console.log(error);
                        }
                    });
                }

                function saveView() {
                    jQuery.ajax({
                        url: 'analytics/save',
                        type: 'POST',
                        data: {
                            'accountId': accountId,
                            'propertyId': propertyId,
                            'viewId': viewId,
                            'foreignId': foreignId,
                        },
                        success: function(data) {
                            console.log(data);
                        },
                        error: function(error) {
                            console.log(error);
                        }
                    });
                }

                function populateDropdown(data, urlPath) {
                    let collection = '<option value="">Choose</option>';
                    for (i=0; i < data.length; i++)
                        collection += '<option value="'+data[i].id+'">'+data[i].name+'</option>';

                    $('#'+urlPath).html(collection);
                }

                $('#accounts').change(function() {
                    accountId = $(this).val();
                    propertyId = '';
                    viewId = '';
                    propertiesDrop.html('');
                    propertiesDrop.hide();
                    viewsDrop.html('');
                    viewsDrop.hide();
                    getAccountData();
                    propertiesDrop.show();
                });

                $('#properties').change(function() {
                    propertyId = $(this).val();
                    viewsDrop.html('');
                    viewsDrop.hide();
                    getAccountData();
                    viewsDrop.show();
                });

                $('#views').change(function() {
                    viewId = $(this).val();
                    // foreignId = 1111;
                    saveView();
                });
            </script>
        @else
            Please connect first. <a href="{{ route('ga.connect') }}">Connect</a>
        @endif
    </body>
</html>
