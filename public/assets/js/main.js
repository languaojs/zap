(function(app){
    app.print = function (printarea){
        if($(printarea).length == 0){
            alert('Print what?');
            return;
        }else{
            $(printarea).printThis({
                importCSS: true,
                importStyle: true,
                copyTagClasses: true,
                copyTagStyles: true
            });
        }
    }
})(window.App = window.App || {})