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
	
	if(!loggedin()) {
		echo json_encode(['success' => false, 'code' => 'NO_LOGIN']);
		exit;
	}

    $stmt = $conn2->prepare('SELECT * FROM model WHERE user = ? AND removed = 0 ORDER BY date DESC LIMIT ' . $limit . ' OFFSET ' . $offset);
    $stmt->bind_param('i', $current_user->id);
    $stmt->execute();
    $result = $stmt->get_result();
    $creations = null;

	if($page <= 1 && $result->num_rows === 0) {
		echo json_encode(['success' => false, 'code' => 'ACC_NO_CREATIONS']);
		exit;
	}

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
            'thumb' => $row['screenshot'] ?: '/img/no_image.png',
            'comments' => Numbers::format($row['replies']),
            'date' => time_ago($row['date']),
        ];
    }

    echo json_encode(['success' => true, 'creations' => $creations]);
    exit;
}

if(isset($_GET['get_creation']) && isset($_GET['id'])) {
    $model_id = $_GET['id'];

	if(!loggedin()) {
		echo json_encode(['success' => false, 'code' => 'NO_LOGIN']);
		exit;
	}

    $stmt = $conn2->prepare('SELECT * FROM model WHERE user = ? AND id = ? AND removed = 0');
    $stmt->bind_param('ii', $current_user->id, $model_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $creation = null;

    if($result->num_rows === 0) {
        echo json_encode(['success' => false, 'code' => 'CREATION_404']);
        exit;
    }

    while ($row = $result->fetch_assoc()) {
        $name = htmlspecialchars($row['name'] ?: 'Untited Creation');

        $creation = [
            'model_id' => $row['id'],
            'title' => $name,
            'description' => $row['description'],
            'thumb' => $row['screenshot'] ?: '/img/no_image.png',
            'date' => time_ago($row['date']),
            'modeler_url' => '/modeler?build_id=' . $row['id'],
            'viewer_url' => '/build/' . $row['id'],
            'visibility' => $row['visibility'],
            'legacy' => (bool)$row['legacy'],
            'can_edit' => (bool)$row['can_edit'],
        ];
    }

    echo json_encode(['success' => true, 'creation' => $creation]);
    exit;
}

if (isset($_POST['edit']) && isset($_POST['id'])) {
    if(!loggedin()) {
        echo json_encode(['success' => false, 'code' => 'NO_LOGIN']);
    }

    $model_id = (int)$_POST['id'];

    $stmt_find = $conn2->prepare('SELECT id, name, legacy FROM model WHERE user = ? AND id = ? AND removed = 0');
    $stmt_find->bind_param('ii', $current_user->id, $model_id);
    $stmt_find->execute();
    $result = $stmt_find->get_result();

    if($result->num_rows === 0) {
        echo json_encode(['success' => false, 'code' => 'CREATION_404']);
        exit;
    }

    $data = $result->fetch_assoc();
    $name = $_POST['title'];
    $about = $_POST['description'];
    $visibility = $_POST['visibility'];
    $editior = (bool)$_POST['can_edit'];

    if((bool)$data['legacy'] === true) {
        $name = $data['name'];
    }

    $stmt = $conn2->prepare("UPDATE model SET name = ?, description = ?, visibility = ?, can_edit = ? WHERE id = ? AND user = ? AND removed = 0");
    $stmt->bind_param("sssiii", $name, $about, $visibility, $editior, $model_id, $current_user->id);
    $result = $stmt->execute();

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Creation updated']);
        exit;
    } else {
        echo json_encode(['success' => false, 'code' => 'EDIT_GENERIC']);
        exit;
    }
}

if (isset($_POST['delete']) && isset($_POST['id'])) {
    header('Content-Type: application/json');
    $model_id = (int)$_POST['id'];

    if ($_SESSION['csrf'] === $_POST['csrf_token']) {
        if (loggedin()) {
            $stmt = $conn2->prepare('SELECT * FROM model WHERE user = ? AND id = ? AND removed = 0');
            $stmt->bind_param('ii', $current_user->id, $model_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows === 0) {
                echo json_encode(['success' => false, 'error' => 'Creation not found']);
                exit;
            }

            $data = $result->fetch_assoc();

            if(trim($current_user->id) === trim($data['user']) || $current_user->admin === true) {
                $conn = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME2);

                $modelFile = basename($data['model']);
                $screenshotFile = basename($data['screenshot']);

                if (file_exists($modelFile)) {
                    @unlink($modelFile);
                }

                if (file_exists($screenshotFile)) {
                    @unlink($screenshotFile);
                }

                $sql2 = "DELETE FROM model WHERE id = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $model_id);

                if ($stmt2->execute()) {
                    $stmtVotes = $conn->prepare("DELETE FROM votes WHERE creation = ?");
                    $stmtVotes->bind_param("i", $model_id);
                    $stmtVotes->execute();

                    $stmtComments = $conn->prepare("DELETE FROM comments WHERE model = ?");
                    $stmtComments->bind_param("i", $model_id);
                    $stmtComments->execute();

                    echo json_encode(['success' => 'Creation deleted']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Error deleting model listing']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'An authentication error has occured']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Oops! Your CSRF token seems to be invalid.']);
    }
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
            const urlparams = new URLSearchParams(window.location.search);
            window.page = 1;

            $("#foward-button").on("click", function() {
                window.page = window.page + 1;
                getCreations(window.page);
            });

			const SERVER_CODES = {
				'NO_LOGIN': 'Please sign in to continue',
				'QUERY_NO_CREATIONS': 'No creations found',
				'ACC_NO_CREATIONS': 'You don\'t have any creations yet',
				'CREATION_404': 'Creation not found',
				'EDIT_GENERIC': 'Failed to update creation',
			}

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
                                $clone.find(".creation").attr('onclick', `window.editCreation(${r.model_id})`);
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
                            $("#foward-button").remove();
                        } else if(response.code) {
                            console.error(response.code);
                            $(`<br /><h4 class='message w3-red w3-card-2 w3-padding w3-round'>${SERVER_CODES[response.code] || 'An error has occured'}</h4>`).insertAfter(elm);
                            $("#foward-button").remove();
                        }
                    },
                    error: function(xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }

            window.editCreation = function(id) {
                if(!id) {
                    return;
                }

                $(".editingbox").remove();
                $(".message").remove();

                
                $.get("/ajax/config.php", {
                    get_csrf_token: true
                }, function(data) {
                    console.log(data);
                    window.csrf_token = data.csrf_token;
                }, "json")
                .fail(function(xhr, text, err) {
                    alert(text);
                });

                $.ajax({
                    url: "",
                    method: "GET",
                    data: { get_creation:true,id:id },
                    dataType: "json",
                    success: function(response) {
                        let elm = $('#creationstab');

                        if(response.success === true && response.creation) {
                            let res = response.creation;
                            let $clone = $($('#gr8-edit-template').html());
                            let title = res.title;
                            let about = res.description;

                            $clone.find("form").attr('data-id', id);
                            $clone.find("#title").val(title);
                            $clone.find("#about").val(about);
                            $clone.find(".creation-thumbnail").attr('src', res.thumb);
                            $clone.find("#visibility").val(res.visibility);
                            $clone.find("#modeler-open").attr('href', res.modeler_url);
                            $clone.find("#viewer-open").attr('href', res.viewer_url);
                            $clone.find('#can_edit').prop('checked', res.can_edit);

                            if(res.legacy === true) {
                                $clone.find("#legacy-warning").removeClass('w3-hide');
                                $clone.find("#title").attr('disabled', true);
                                $clone.find("#title").addClass('w3-disabled');
                                $clone.find("#modeler-open").remove();
                            }

                            if(res.visibility === 'private') {
                                $clone.find("#viewer-open").remove();
                            }

                            $('body').append($clone);
                            window.mode();

                            $("#edit-creation-form #edit-confirm").on("click", function(e) {
                                e.preventDefault();

                                let payload = {
                                    edit: true,
                                    id: id,
                                    title: $("#edit-creation-form #title").val(),
                                    description: $("#edit-creation-form #about").val(),
                                    visibility: $("#edit-creation-form #visibility").val(),
                                    can_edit: $('#edit-creation-form #can_edit').is(':checked'),
                                };

                                $.ajax({
                                    url: '',
                                    type: 'POST',
                                    data: payload,
                                    dataType: 'json',
                                    success: function(response) {
                                        if(response.success) {
                                            $(".editingbox").remove();
                                            $('#creationstab').children().not('template').remove();
                                            window.getCreations(1);
                                            $(`<h4 class='message w3-light-grey w3-card-2 w3-padding w3-round'>${response.message}</h4>`).insertBefore(elm);
                                            $("html, body").animate({ scrollTop: 0 }, "slow");
                                        } else if(response.code) {
                                            console.error(response.code);
                                            $(".editingbox").remove();
                                            $(`<h4 class='message w3-red w3-card-2 w3-padding w3-round'>${SERVER_CODES[response.code] || 'An error has occured'}</h4>`).insertBefore(elm);
                                            $("html, body").animate({ scrollTop: 0 }, "slow");
                                        }
                                    },
                                    error: function() {
                                        alert("A server error occurred.");
                                    }
                                });
                            });

                            $("#edit-creation-form #edit-delete").on("click", function(e) {
                                e.preventDefault();

                                $.ajax({
                                    type: "POST",
                                    data: { id: id, csrf_token: window.csrf_token, delete: true },
                                    dataType: "json",
                                    success: function(response) {
                                        if (response.success) {
                                            alert(response.success);
                                            $(".editingbox").remove();
                                            $('#creationstab').children().not('template').remove();
                                            window.getCreations(1);
                                        } else if(response.error) {
                                            console.error(response.error);
                                            $(".editingbox").remove();
                                            $(`<h4 class='message w3-red w3-card-2 w3-padding w3-round'>${response.error}</h4>`).insertBefore(elm);
                                            $("html, body").animate({ scrollTop: 0 }, "slow");
                                        }
                                    },
                                    error: function() {
                                        alert("An error occurred. Please try again later.");
                                    }
                                });
                            });
                        } else if(response.code) {
                            console.error(response.code);
                            $(`<h4 class='message w3-red w3-card-2 w3-padding w3-round'>${SERVER_CODES[response.code] || 'An error has occured'}</h4>`).insertBefore(elm);
                            $("html, body").animate({ scrollTop: 0 }, "slow");
                        }
                    },
                    error: function(xhr, stat, err) {
                        console.error(stat, xhr.status, err);
                    }
                });
            }

            getCreations(1);

            const edit_id = urlparams.get('edit');
            if(edit_id) {
                window.editCreation(edit_id);
            }
        });
    </script>

    <span data-testid="gr8-my-creations-text-sb">
        <p data-testid="gr8-my-creations-text-sb--text">Creations are sorted by newest by default.</p>
    </span>

    <div id="creationstab" class="w3-row-padding">
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

    <!--<template id="gr8-edit-template">
        <div id="editingbox" class="editingbox w3-row w3-section">
            <form method="post" action="" class="w3-col s12 w3-light-grey w3-card-2 w3-border w3-border-grey w3-padding-large w3-round">
                <span onclick='document.getElementById("editingbox").style.display="none"' class="w3-btn w3-red w3-hover-white w3-padding w3-right">&times;</span>
                <b>Edit creation</b><br />
                <p>
                    <label>Name:</label>
                    <textarea name="title" id="title" class="w3-input w3-border" rows="1"></textarea>
                </p>
                <p>
                    <label>Description:</label>
                    <textarea name="about" id="about" class="w3-input w3-border" rows="2"></textarea>
                </p>
                <p>
                    <button name="edit-confirm" id="edit-confirm" class="w3-btn w3-blue w3-hover-white w3-quarter w3-border w3-border-indigo">Update</button>
                </p>
            </form>
        </div>
    </template>-->

    <template id="gr8-edit-template">
        <div class="w3-modal editingbox" style="display:block;">
            <div class="w3-modal-content w3-card-4 w3-animate-zoom" style="max-width:600px">

                <header class="w3-container w3-blue w3-padding">
                    <span onclick="$('.editingbox').remove()" class="w3-button w3-display-topright w3-large w3-hover-red">&times;</span>
                    <h3>Edit Creation</h3>
                    <h4 id="legacy-warning" class="w3-card-2 w3-yellow w3-padding w3-round w3-hide">This creation is <b>legacy</b>, meaning it cannot be viewed in the viewer or edited in the modeler.</h4>
                </header>

                <form id="edit-creation-form" class="creation-card w3-container w3-padding-16 w3-light-grey">
                    <p>
                        <img src="" alt="" title="" class="creation-thumbnail">
                    </p>

                    <p>
                        <label class="w3-text-blue"><b>Name</b></label>
                        <textarea name="title" id="title" class="w3-input w3-border w3-round" rows="1"></textarea>
                    </p>

                    <p>
                        <label class="w3-text-blue"><b>Description</b></label>
                        <textarea name="about" id="about" class="w3-input w3-border w3-round" rows="3"></textarea>
                    </p>

                    <p>
                        <label class="w3-text-blue"><b>Visibility</b></label>
                        <select name="visibility" id="visibility" class="w3-select w3-border w3-round">
                            <option value="public">Public</option>
                            <option value="unlisted">Unlisted</option>
                            <option value="private">Private</option>
                        </select>
                    </p>

                    <p>
                        <input type="checkbox" id="can_edit" name="can_edit">
                        <label for="can_edit"> Let other people open this creation in the modeler</label><br>
                    </p>

                    <p>
                        <label class="w3-text-blue"><b>Links</b></label><br />
                        <a id="modeler-open" href="">Edit in modeler</a><br />
                        <a id="viewer-open" href="">View public page</a>
                    </p>
                    
                    <div class="w3-padding-16">
                        <span class="w3-left">
                            <button type="submit" name="edit-delete" id="edit-delete" class="w3-button w3-red w3-round">Delete</button>
                        </span>

                        <span class="w3-right">
                            <button type="button" onclick="$('.editingbox').remove()" class="w3-button w3-red w3-round">Cancel</button>
                            <button type="submit" name="edit-confirm" id="edit-confirm" class="w3-button w3-blue w3-round">Update</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <button id="foward-button" class="w3-btn w3-large w3-block w3-light-grey w3-border">LOAD MORE</button>

    <br /><br />
    <?php include('../linkbar.php') ?>
</body>
</html>