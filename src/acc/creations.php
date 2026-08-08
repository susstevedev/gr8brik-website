<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/time.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ajax/numbers.php';

if (!loggedin()) {
    header('Location:login.php');
}

$conn2 = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);
if ($conn2->connect_error) {
    exit($conn2->connect_error);
}

if(isset($_GET['my_creations']) && isset($_GET['page'])) {
    $page = $_GET['page'] ?: 1;
    $limit = 9;
    $offset = ($page - 1) * $limit;

    $stmt = $conn2->prepare('SELECT * FROM model WHERE user = ? ORDER BY date DESC LIMIT ' . $limit . ' OFFSET ' . $offset);
    $stmt->bind_param('i', $current_user->id);
    $stmt->execute();
    $result = $stmt->get_result();
    $creations = null;

    while ($row = $result->fetch_assoc()) {
        $model_id = $row['id'];

        $name = htmlspecialchars(substr($row['name'] ?: 'Untited Creation', 0, 30));
        if (strlen($row['name']) >= 30) {
            $name .= '...';
        }

        $creations[] = [
            'model_id' => $row['id'],
            'title' => $name,
            'views' => Numbers::format($row['views']),
            'likes' => Numbers::format($row['likes']),
            'size' => Numbers::filesize($row['size']),
            'thumb' => $row['screenshot'],
            'comments' => Numbers::format($row['replies']),
            'date' => time_ago($row['date']),
        ];
    }

    echo json_encode(['success' => true, 'creations' => $creations]);
    exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>My Creations</title>
    <?php include '../header.php' ?>
</head>
<body class="w3-light-blue w3-container">
    <?php
        include('../navbar.php');
        include('panel.php');
    ?>

    <script>
        $(document).ready(function() {
            window.page = 1;

            $("#foward-button").on("click", function() {
                window.page = window.page + 1;
                getCreations(window.page);
            });

            window.getCreations = function(page) {
                $.ajax({
                    url: "",
                    method: "GET",
                    data: { my_creations:true,page:page },
                    dataType: "json",
                    success: function(response) {
                        let elm = $('#creationstab');

                        if(response.success === true && response.creations) {
                            response.creations.forEach(function(r) {
                                let $clone = $($('#gr8-creation-template').html());
                                console.log(r);

                                $clone.find(".creation").attr('data-id', r.model_id);
                                $clone.find(".creation").attr('onclick', "window.editCreation(this)");
                                $clone.find(".creation-title").text(r.title);
                                $clone.find(".meta-author span").text(r.date);
                                $clone.find(".creation-thumbnail").attr("src", r.thumb);
                                $clone.find(".views").append(r.views + ' views');
                                $clone.find(".favs").append(r.likes + ' favorites');
                                $clone.find(".size").append(r.size);
                                $clone.find(".comments").append(r.comments + ' comments');

                                elm.append($clone);
                            });
                            window.mode();
                        } else if(response.creations === null) {
                            $(`<br /><div class='message w3-padding w3-round w3-light-grey'>No creations to load</div>`).insertAfter(elm);
                            $("#foward-button").remove();
                        } else if(response.error) {
                            console.error(response.error);
                            $(`<br /><div class='message w3-padding w3-round w3-red'>${response.error}</div>`).insertAfter(elm);
                            $("#foward-button").remove();
                        }
                    },
                    error: function(xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }

            window.editCreation = function(elm) {
                let id = $(elm).data('id'); 
                if(!id) {
                    return;
                }

                $(".editingbox").remove();
                console.log(id);

                let $clone = $($('#gr8-edit-template').html());
                let title = $(elm).find(".creation-title").text();

                $clone.find("form").attr('data-id', id);
                $clone.find("form").addClass('editingbox');
                $clone.find("#title").text(title);
                $clone.find("#about").text(title);

                $(elm).after($clone);
            }

            getCreations(1);
        });
    </script>

    <div id="creationstab" class="w3-row-padding">
        <span data-testid="gr8-my-creations-text-sb">
            <p data-testid="gr8-my-creations-text-sb--text">Creations are sorted by newest by default.</p>
        </span>

        <template id="gr8-creation-template">
            <div class="w3-col l4 m6 s12 w3-margin-bottom">
                <div class="gr8-theme creation w3-card-2 w3-light-grey w3-padding creation-card">
                    <div class="creation-link">
                        <img src="" loading="lazy" class="cre-image w3-card-2 w3-grey creation-thumbnail">
                        <h4 class="creation-title"></h4>
                    </div>
                    <div class="creation-meta">
                        <span class="meta-author">
                            By you on <span></span>
                        </span>

                        <div class="meta-stats">
                            <span class="views"><i class="fa fa-eye"></i> </span> •
                            <span class="favs"><i class="fa fa-star"></i> </span> •
                            <span class="comments"><i class="fa fa-comments"></i> </span>
                            <span class="size w3-right"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div><br />

    <template id="gr8-edit-template">
        <form method='post' action=''>
            <div class='w3-border w3-border-grey w3-padding-large w3-round'>
                <b>Edit creation</b><br />
                <p>Name: <textarea name='title' id="title" value='' rows='1' cols='80'></textarea></p>
                <p>Description: <textarea name='about' id="about" value='' rows='2' cols='80'></textarea></p>
                <p><button name='edit-confirm' id="edit-confirm" class='w3-btn w3-blue w3-hover-white w3-quarter w3-border w3-border-indigo'>Update</button></p>
            </div>
        </form><br />
    </template>

    <button id="foward-button" class="w3-btn w3-large w3-block w3-light-grey w3-border">LOAD MORE</button>

    <br /><br />
    <?php include('../linkbar.php') ?>
</body>
</html>