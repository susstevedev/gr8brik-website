$(document).ready(function() {
    var slideIndex = 1;
    showDivs(slideIndex);

    window.plusDivs = function(n) {
        showDivs(slideIndex += n);
    }

    setInterval(function() {
        plusDivs(1);
    }, 5000);

    function showDivs(n) {
        if (n > $(".slides").length) {
            slideIndex = 1;
        }
        if (n < 1) {
            slideIndex = $(".slides").length;
        }
        $(".slides").hide();
        $(".slides").eq(slideIndex - 1).show();
    }

    $.ajax({
        url: '/list.php',
        type: 'GET',
        data: {
            feature_v3: true
        },
        dataType: 'json',
        success: function(res) {
            let elm = $('#featured-builds')
            elm.children().not('#gr8-creation-template').remove();

            res.creations.forEach(function(r) {
                let $clone = $($('#gr8-creation-template').html());

                $clone.find(".creation-title").text(r.title);
                $clone.find(".creation-link").attr("href", "/build/" + r.model_id);
                $clone.find(".meta-author a").append(r.username);
                $clone.find(".meta-author a").attr("href", "/@" + r.username);
                $clone.find(".meta-author span").text(r.date);
                $clone.find(".creation-thumbnail").attr("src", r.thumb);
                $clone.find(".views").append(r.views + ' views');
                $clone.find(".favs").append(r.likes + ' favorites');
                $clone.find(".comments").append(r.comments + ' comments');

                elm.append($clone);
            });
            window.mode();
            $("#featured-builds-loader").fadeOut('fast');
            elm.fadeIn('fast');
        }
    });
});