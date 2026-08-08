$(document).ready(function() {
    // Source - https://stackoverflow.com/a/476681
    // Posted by James
    // Retrieved 2026-08-05, License - CC BY-SA 2.5

    $.fn.preload = function() {
        this.each(function(){
            $('<img/>')[0].src = this;
        });
    }
});