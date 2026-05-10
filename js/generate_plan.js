// Make "Recent Plans > View" update output smoothly when plan exists
    (function(){
        var pre = document.querySelector('.plan-output pre');
        if (pre) pre.id = 'planOutput';
    })();