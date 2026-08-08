<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
if (loggedin()) {
    header('Location:list.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>LDraw and WebGL based LEGO&reg; building webapp with social features in your web browser.</title>
    <?php include 'header.php' ?>
</head>

<body class="w3-container w3-light-blue">
    <?php include 'navbar.php' ?>

    <style>
        .main-text {
            max-width: 75%;
        }

        .slides {
            width: 500px;
            height: 250px;
            display: none;
        }

        @media only screen and (max-width: 768px) {
            .main-image {
                max-width: 0px;
                height: 0px;
                display: none;
            }

            .main-text {
                min-width: 100%;
                max-width: 100%;
            }
        }
    </style>

    <script>
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
        });
    </script>

    <div class="w3-container w3-light-grey w3-card-2 w3-text-grey">
        <h2>GR8BRIK<span class="w3-opacity">.rf.gd</span></h2>
    </div><br />
    <center>
        <article class="main-text w3-container">
            <div class="w3-content w3-card-2 w3-light-grey w3-padding-small w3-left w3-round" style="max-width:525px;position:relative;">
                <a href="#feed"><img class="slides w3-animate-right" src="img/feed.jpg"></a>
                <a href="#community"><img class="slides w3-animate-right" src="img/com.jpg"></a>
                <a href="#creations"><img class="slides w3-animate-right" src="img/creations.jpg"></a>
                <a href="#uploads"><img class="slides w3-animate-right" src="img/upload.jpg"></a>

                <a style="position:absolute;top:45%; left:10px;" onclick="plusDivs(-1)"><button class="w3-btn w3-blue w3-round w3-padding w3-large w3-hover-white"><i class="fa fa-arrow-left"></i></button></a>
                <a style="position:absolute; top:45%; right:10px;" onclick="plusDivs(1)"><button class="w3-btn w3-blue w3-round w3-padding w3-large w3-hover-white"><i class="fa fa-arrow-right"></i></button></a>
            </div>

            <p style="word-wrap: break-word;">Gr8brik is an LDraw and WebGL based LEGO building webapp with social features in running completly your web browser. No download or installation needed, optionally store your creations in your account.</p>
            <a href="/modeler" target="_blank"><button class="w3-btn w3-large w3-light-grey w3-hover-opacity w3-round-small w3-border w3-border-grey">Start Building!</button></a> <a href="/acc/login?goto=/acc/creations" target="_blank">
                <button class="w3-btn w3-large w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Login or register</button>
            </a><br />
            <p>
                <a href="/com/view?id=17">Community Guidelines</a> • <a href="/terms">Terms and Conditions</a> • <a href="/privacy">Privacy Policy</a><br />
            </p>
        </article>
    </center>

    <hr />
    <div class="w3-container grid">
        <article id="community">
            <h2>Awesome community</h2>
            <p>The community section is a forum to post about Gr8Brik and other tools. You can view posts of others, reply to them, and create your own posts.</p>
            <a href="/com/"><button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Community</button></a>
        </article>
        <hr />

        <article id="creations">
            <h2>Cool user creations</h2>
            <p>You can view, download, edit, and also comment on other users creations. You can also report creations that do not follow the rules.</p>
            <a href="/list"><button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Creations</button></a>
        </article>
        <hr />

        <article id="uploads">
            <h2>Fast, quick, and easy modeler</h2>
            <p>The modeler let's you create and edit 3D ldraw and gr8brik models. Free without download or installation.</p>
            <a href="/modeler"><button class="w3-btn w3-blue w3-hover-opacity w3-round-small w3-border w3-border-indigo">Create</button></a>
        </article>
        <hr />
    </div>

    <script>
        let featured;
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
                    $clone.find(".meta-author a b").text(r.username);
                    $clone.find(".meta-author a").attr("href", "/@" + r.username);
                    $clone.find(".meta-author span").text(r.date);
                    $clone.find(".creation-thumbnail").attr("src", r.thumb);
                    $clone.find(".views").append(r.views + ' views');
                    $clone.find(".favs").append(r.likes + ' favorites');
                    $clone.find(".comments").append(r.comments + ' comments');

                    elm.append($clone);
                });
                window.mode();
                elm.removeClass('w3-hide').fadeIn(500);
                $("#featured-builds-loader").fadeOut(500);
            }
        });
    </script>

    <div class="container">
        <h2>Featured Creations</h2>
        <span aria-busy="true" id="featured-builds-loader">Loading creations...</span>
        <span id="featured-builds" class="w3-row-padding w3-hide">
            <template id="gr8-creation-template">
                <!--<div class='creation w3-left w3-padding-large'>
                    <a class='link-thumb' href='/build/'>
                        <img src='' width='320px' height='240px' loading='lazy' class='screenshot w3-grey w3-card-2 w3-hover-shadow'>
                    </a>
                    <div class='w3-card-2 gr8-theme w3-light-grey w3-padding-small'>
                        <a class='link-name' href='/build/'>
                            <h4 class='name'></h4>
                        </a>
                        <span>By <a class='user' href='/user/'></a> on <span class='time'></span></span>
                        <br /><span><span class='views'></span> views • <span class='favs'></span> favorites</span>
                    </div>
                </div>-->

                <div class="w3-col l4 m6 s12 w3-margin-bottom">
                    <div class="gr8-theme liked w3-card-2 w3-light-grey w3-padding creation-card">
                        <a href="/build/" class="creation-link">
                            <img src="" loading="lazy" class="cre-image w3-hover-opacity w3-card-2 w3-grey creation-thumbnail">
                            <h4 class="creation-title"></h4>
                        </a>
                        <div class="creation-meta">
                            <span class="meta-author">
                                By <a href=""><b></b></a> on <span></span>
                            </span>

                            <div class="meta-stats">
                                <span class="views"><i class="fa fa-eye"></i> </span> •
                                <span class="favs"><i class="fa fa-star"></i> </span> •
                                <span class="comments"><i class="fa fa-comments"></i> </span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </span>
    </div>

    <?php include('linkbar.php'); ?>

</body>

</html>