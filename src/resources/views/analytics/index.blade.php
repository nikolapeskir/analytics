<html>
    <head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <body>
        @if($gaAnalytics)
            @if(count($gaAnalytics->service->getAccounts()) > 0)
                <i id="ga-accounts-refresh" class="icon-serp-refresh ic-success" title="Refresh List"></i>
                <select id="ga-accounts" name="gaAccounts" class="form-control input-lg">
                    <option value="">Choose Account</option>
                    @foreach($gaAnalytics->service->getAccounts() as $account)
                        <option value="{{ $account['id'] }}">{{ $account['name'] }}</option>
                    @endforeach
                </select>
                <i id="ga-accounts-step" class="icon-serp-chevron-left" title="Choose Account" style="display: none;"></i>
                <i id="ga-properties-refresh" class="icon-serp-refresh ic-success" title="Refresh List" style="display: none;"></i>
                <select id="ga-properties" name="gaProperties" style="display: none;" class="form-control input-lg">
                </select>
                <i id="ga-properties-step" class="icon-serp-chevron-left" title="Choose Property" style="display: none;"></i>
                <i id="ga-views-refresh" class="icon-serp-refresh ic-success" title="Refresh List" style="display: none;"></i>
                <select id="ga-views" name="gaViews" style="display: none;" class="form-control input-lg">
                </select>
                <i id="ga-delete-view" class="icon-serp-close" title="Delete Google Analytics View" style="display: none;"></i>
                <i id="ga-view-saved" class="icon-serp-check ic-success" style="display: none;"></i>
                <style>
                    select#ga-accounts,
                    select#ga-properties,
                    select#ga-views {
                        width: 60%;
                        margin: 0;
                        display: inline-block;
                        padding-left: 3em;
                    }
                    #ga-delete-view {
                        font-weight: bold;
                        cursor: pointer;
                        padding: 1em;
                    }
                    #ga-accounts-refresh,
                    #ga-properties-refresh,
                    #ga-views-refresh {
                        font-weight: bold;
                        position: absolute;
                        padding: 1em 0.5em;
                        cursor: pointer;
                    }
                    #ga-accounts-step,
                    #ga-properties-step {
                        padding: 1em 0.5em;
                        cursor: pointer;
                    }
                    #ga-accounts-step:hover,
                    #ga-properties-step:hover {
                        background-color: #ecf2f6;
                    }
                </style>
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
                <script type="text/javascript">
                    var gaAccountId = '',
                        gaPropertyId = '',
                        gaViewId = '',
                        gaForeignId = '',
                        gaAccountsDrop = $('#ga-accounts'),
                        gaAccountsRefresh = $('#ga-accounts-refresh'),
                        gaAccountsStep = $('#ga-accounts-step'),
                        gaPropertiesDrop = $('#ga-properties'),
                        gaPropertiesRefresh = $('#ga-properties-refresh'),
                        gaPropertiesStep = $('#ga-properties-step'),
                        gaViewsDrop = $('#ga-views'),
                        gaViewsRefresh = $('#ga-views-refresh'),
                        gaDeleteView = $('#ga-delete-view'),
                        gaViewSaved = $('#ga-view-saved'),
                        gaRefreshDropdown = '',
                        urlGaPath = (gaAccountId !== '' && gaPropertyId !== '')
                            ? 'views'
                            : 'properties',
                        gaAccountView = '';

                    @if(isset($project->id))
                            @if($gaAnalytics->getViewById($project->id))
                        gaAccountView = {!! $project->getAnalyticsView()->first() !!};
                    @endif
                    @endif

                    function ajaxRequest(params, callback) {
                        var url = (params.url !== undefined) ? params.url : '',
                            data = (params.data !== undefined) ? params.data : '',
                            requestType = (params.requestType !== undefined) ? params.requestType : 'POST',
                            contentType = (params.contentType !== undefined) ? params.contentType : 'application/json';

                        $.ajax({
                            url: url,
                            type: requestType,
                            contentType: contentType,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: data,
                        })
                            .done(function(data) {
                                callback(data);
                            })
                            .fail(function(error) {
                                console.log(error);
                            });
                    }

                    function getGaAccountData() {
                        let data = {
                            'accountId': gaAccountId,
                            'propertyId': gaPropertyId,
                        };

                        urlGaPath = (gaAccountId !== '' && gaPropertyId !== '')
                            ? 'views'
                            : 'properties';

                        ajaxRequest(
                            {
                                url: '{{ config("app.url") }}/analytics/' + urlGaPath,
                                requestType: 'POST',
                                contentType: 'application/x-www-form-urlencoded',
                                data : data
                            },
                            populateGaDropdown
                        );
                    }

                    function refreshGaDropdown(data) {
                        if (gaRefreshDropdown === 'accounts'
                            || gaRefreshDropdown === 'properties'
                            || gaRefreshDropdown === 'views'
                        ) {
                            ajaxRequest(
                                {
                                    url: '{{ config("app.url") }}/analytics/' + gaRefreshDropdown,
                                    requestType: 'POST',
                                    contentType: 'application/x-www-form-urlencoded',
                                    data : data
                                },
                                handleGaRefreshDropdown
                            );
                        }
                    }

                    function saveGaView() {
                        let data = {
                            'accountId': gaAccountId,
                            'propertyId': gaPropertyId,
                            'viewId': gaViewId,
                            'foreignId': gaForeignId,
                        };

                        ajaxRequest(
                            {
                                url: '{{ config("app.url") }}/analytics/save',
                                requestType: 'POST',
                                contentType: 'application/x-www-form-urlencoded',
                                data : data
                            },
                            handleGaSaveView
                        );
                    }

                    function handleGaSaveView(data) {
                        if (data.id !== undefined)
                            gaDeleteView.show();
                        // gaViewSaved.show();
                    }

                    function populateGaDropdown(data) {
                        let dropName = (urlGaPath === 'properties') ? 'Property' : 'View',
                            collection = '<option value="">Choose ' + dropName + '</option>';

                        for (i=0; i < data.length; i++)
                            collection += '<option value="'+data[i].id+'">'+data[i].name+'</option>';

                        $('#ga-'+urlGaPath).html(collection);

                        if (urlGaPath === 'properties') {
                            gaAccountsDrop.hide();
                            gaAccountsRefresh.hide();
                            gaAccountsStep.show();

                            gaPropertiesDrop.val(gaPropertyId);
                            gaPropertiesRefresh.show();
                            gaPropertiesDrop.show();

                            if (gaAccountView.view_id !== undefined) {
                                gaViewId = gaAccountView.view_id;
                                getGaAccountData();
                                gaDeleteView.show();
                            }
                        }

                        if (urlGaPath === 'views') {
                            gaAccountsStep.hide();

                            gaPropertiesStep.show();
                            gaPropertiesRefresh.hide();
                            gaPropertiesDrop.hide();

                            gaViewsDrop.val(gaViewId);
                            gaViewsRefresh.show();
                            gaViewsDrop.show();
                        }

                    }

                    function deleteGaView() {
                        ajaxRequest(
                            {
                                url: '{{ config("app.url") }}/analytics/delete/' + gaForeignId,
                                requestType: 'POST',
                            },
                            handleGaViewDelete
                        );
                    }

                    function handleGaRefreshDropdown(data) {
                        let name = 'View',
                            collection = '';

                        if (gaRefreshDropdown === 'accounts') {
                            name = 'Account';
                        } else if (gaRefreshDropdown === 'properties') {
                            name = 'Property';
                        }

                        collection = '<option value="">Choose ' + name + '</option>';

                        for (i=0; i < data.length; i++)
                            collection += '<option value="'+data[i].id+'">'+data[i].name+'</option>';

                        $('#ga-'+gaRefreshDropdown+'-refresh').fadeIn('slow');
                        $('#ga-'+gaRefreshDropdown).html(collection);

                        gaRefreshDropdown = '';
                    }

                    function handleGaViewDelete(data) {
                        if (data != 0) {
                            resetGaSteps();
                        }
                    }

                    function resetGaStep1() {
                        gaViewId = '';
                        gaViewsRefresh.hide();
                        gaViewsDrop.hide();
                        gaViewsDrop.html('');

                        gaPropertyId = '';
                        gaPropertiesStep.hide();
                        gaPropertiesRefresh.hide();
                        gaPropertiesDrop.hide();
                        gaPropertiesDrop.html('');

                        gaAccountsStep.hide();
                        gaAccountsRefresh.show();
                        gaAccountsDrop.show();
                    }

                    function resetGaStep2() {
                        gaViewId = '';
                        gaViewsRefresh.hide();
                        gaViewsDrop.hide();
                        gaViewsDrop.html('');

                        gaPropertiesStep.hide();
                        gaPropertiesRefresh.show();
                        gaPropertiesDrop.show();

                        gaAccountsStep.show();
                    }

                    function resetGaSteps() {
                        gaViewSaved.hide();
                        gaDeleteView.hide();

                        gaViewId = '';
                        gaViewsDrop.html('');
                        gaViewsRefresh.hide();
                        gaViewsDrop.hide();

                        gaPropertyId = '';
                        gaPropertiesStep.hide();
                        gaPropertiesDrop.html('');
                        gaPropertiesRefresh.hide();
                        gaPropertiesDrop.hide();

                        gaAccountId = '';
                        gaAccountsStep.hide();
                        gaAccountsRefresh.show();
                        gaAccountsDrop.show();
                        gaAccountsDrop.val(gaAccountId);
                    }

                    gaAccountsRefresh.click(function() {
                        gaAccountsRefresh.fadeOut('slow');
                        gaRefreshDropdown = 'accounts';
                        refreshGaDropdown({
                            'refresh': true
                        });
                    });

                    gaAccountsDrop.change(function() {
                        gaAccountId = $(this).val();

                        if (gaAccountId !== '') {
                            gaPropertyId = '';
                            gaViewId = '';
                            gaAccountsRefresh.hide();
                            gaAccountsDrop.hide();
                            gaAccountsStep.show();

                            gaPropertiesDrop.html('<option value="">Loading Properties...</option>');
                            gaPropertiesDrop.show();
                            getGaAccountData();
                        }
                    });

                    gaAccountsStep.click(function () {
                        resetGaStep1();
                    });

                    gaPropertiesRefresh.click(function() {
                        gaPropertiesRefresh.fadeOut('slow');
                        gaRefreshDropdown = 'properties';
                        refreshGaDropdown({
                            'accountId': gaAccountId,
                            'refresh': true
                        });
                    });

                    gaPropertiesDrop.change(function() {
                        gaPropertyId = $(this).val();

                        if (gaPropertyId !== '') {
                            gaViewId = '';
                            gaAccountsStep.hide();
                            gaPropertiesRefresh.hide();
                            gaPropertiesDrop.hide();
                            gaPropertiesStep.show();

                            gaViewsDrop.show();
                            gaViewsDrop.html('<option value="">Loading Views...</option>');
                            getGaAccountData();
                        }
                    });

                    gaPropertiesStep.click(function () {
                        resetGaStep2();
                    });

                    gaViewsRefresh.click(function() {
                        gaViewsRefresh.fadeOut('slow');
                        gaRefreshDropdown = 'views';
                        refreshGaDropdown({
                            'accountId': gaAccountId,
                            'propertyId': gaPropertyId,
                            'refresh': true
                        });
                    });

                    gaViewsDrop.change(function() {
                        gaViewId = $(this).val();
                        if (gaForeignId !== ''
                            && gaAccountId !== ''
                            && gaPropertyId !== ''
                            && gaViewId !== ''
                        ) {
                            saveGaView();
                        }

                    });

                    gaDeleteView.click(function () {
                        deleteGaView();
                    });

                    $(document).ready(function() {
                        if (gaAccountView !== '') {
                            gaAccountId = gaAccountView.account_id;
                            gaAccountsDrop.val(gaAccountId);

                            getGaAccountData();
                            gaPropertyId = gaAccountView.property_id;
                        }
                    })
                </script>
            @else
                You don't have Google Analytics Accounts.
            @endif
        @else
            <a href="{{ route('ga.connect') }}">Click Here</a> to connect your Google Analytics account.
        @endif
    </body>
</html>
