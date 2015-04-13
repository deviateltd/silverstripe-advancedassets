jQuery(function($) {
    "use strict";
    $(document).ready(function (){
        var current_page_link = window.location.pathname;
        $('a.secured').each(function(){
            $(this).prop('href', $(this).prop('href')+"?ContainerURL="+current_page_link);
        });
    });
});