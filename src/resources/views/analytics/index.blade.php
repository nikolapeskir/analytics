<html>
    <head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <body>
        <select id="accounts">
            <option value="">Choose</option>
            @foreach($accounts as $account)
                <option value="{{  $account['id'] }}">{{  $account['name'] }}</option>
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

            function populateDropdown(data, urlPath) {
                let collection = '<option value="">Choose</option>';
                for (i=0; i < data.length; i++)
                    collection += '<option value="'+data[i].id+'">'+data[i].name+'</option>';

                $('#'+urlPath).html(collection);
            }

            $('#accounts').change(function() {
                accountId = $(this).val();
                propertyId = '';
                propertiesDrop.html('');
                propertiesDrop.hide();
                viewsDrop.hide();
                getAccountData();
                propertiesDrop.show();
            });

            $('#properties').change(function() {
                propertyId = $(this).val();
                getAccountData();
                viewsDrop.show();
            });

            $('#views').change(function() {
                viewId = $(this).val();
            });
        </script>
    </body>
</html>

