<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
@if(session('code'))
    <script language="Javascript" type="text/javascript">
        jQuery(document).ready(function(){
            opener.gaSetupFailed("{{ session('message') }}");
        });
    </script>
@elseif($connected)
    <script language="Javascript" type="text/javascript">
        jQuery(document).ready(function(){
            opener.loadGaSetup();
        });
    </script>
@else
    <script language="Javascript" type="text/javascript">
        jQuery(document).ready(function(){
            opener.gaWindowClose();
        });
    </script>
@endif

<script>
    console.log('success loaded');
</script>
