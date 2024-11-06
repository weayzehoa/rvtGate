    {{-- REQUIRED SCRIPTS --}}
    <script src="{{ asset('vendor/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script> --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script> --}}
    {{-- <script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script> --}}
    {{-- AdminLTE App --}}
    <script src="{{ asset('js/adminlte.js') }}"></script>
    {{-- Auto Logout --}}
    {{-- <script>
        $(document).ready(function() {
        //10分鐘未移動滑鼠或按任何鍵,則自動登出
            checkState();
            function checkState() {
                document.onmousedown = ReCalculate;
                document.onmousemove = ReCalculate;
                document.onkeydown = ReCalculate;
                ReCalculate()
            }
            function ReCalculate() {
                var oTimerId;
                var logOutUrl = "{{ 'https://'.env('ADMIN_DOMAIN').'/logout' }}";
                clearTimeout(oTimerId);
                oTimerId = setTimeout("location='"+logOutUrl+"'", 10 * 60 * 1000);
            }
        });
    </script> --}}
