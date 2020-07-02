<?php
    session_start();
    if (!isset($_SESSION['name']))
    {
        die('Not logged in');
    }

    require_once "pdo.php";
    require_once "util.php";

    if (isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) && isset($_POST['headline']) && isset($_POST['summary']))
    {
            if (strlen($_POST['first_name']) < 1 || strlen($_POST['last_name']) < 1 || strlen($_POST['email']) < 1 || strlen($_POST['headline']) < 1 || strlen($_POST['summary']) < 1)
            {
                $_SESSION['error'] = 'All values are required';
                header("Location: add.php");
                return;
            }
            else if (strpos($_POST['email'], '@')==false)
            {
                $_SESSION['error']='The mail must contain (@) sign';
            }
            else
            {
                $msg = validatePos();
                if(is_string($msg))
                {
                    $_SESSION['error'] = $msg;
                    header("Location: add.php");
                    return;
                }
                  // Data is valid - time to insert
                $stmt = $pdo->prepare('INSERT INTO Profile (user_id, first_name, last_name, email, headline, summary) VALUES ( :uid, :fn, :ln, :em, :he, :su)');
                $stmt->execute(array(
                    ':uid' => $_SESSION['user_id'],
                    ':fn' => $_POST['first_name'],
                    ':ln' => $_POST['last_name'],
                    ':em' => $_POST['email'],
                    ':he' => $_POST['headline'],
                    ':su' => $_POST['summary'])
                );
                $stmt = $pdo->prepare('SELECT profile_id FROM profile WHERE first_name = :fn');
                $stmt->execute(array(
                  'fn' => $_POST['first_name']
                ));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ( $row === false )
                {
                    $_SESSION['error'] = 'Bad value for profile_id';
                    header( 'Location: add.php' ) ;
                    return;
                }

                $profile_id = htmlentities($row['profile_id']);

                $rank = 1;
                for($i=1; $i<=9; $i++)
                {
                    if ( ! isset($_POST['year'.$i]) ) continue;
                    if ( ! isset($_POST['desc'.$i]) ) continue;

                    $year = $_POST['year'.$i];
                    $desc = $_POST['desc'.$i];
                    $stmt = $pdo->prepare('INSERT INTO Position (profile_id, rank, year, description) VALUES ( :pid, :rank, :year, :desc)');
                    $stmt->execute(array(
                            ':pid' => $_REQUEST['profile_id'],
                            ':rank' => $rank,
                            ':year' => $year,
                            ':desc' => $desc)
                    );
                    $rank++;
                }

                $_SESSION['success'] = "Profile added.";
                header("Location: index.php");
                return;
            }

    }

?>


<!DOCTYPE html>
<html>
<head>
  <title>Ambika Patidar's Profile Add</title>
  <?php require_once "bootstrap.php"; ?>
  <link rel="stylesheet"
    href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"
    integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7"
    crossorigin="anonymous">

  <link rel="stylesheet"
    href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css"
    integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r"
    crossorigin="anonymous">

  <script
    src="https://code.jquery.com/jquery-3.2.1.js"
    integrity="sha256-DZAnKJ/6XZ9si04Hgrsxu/8s717jcIzLy3oi35EouyE="
    crossorigin="anonymous"></script>

</head>
<div class="container">
<body style="font-family: sans-serif;">
  <h1>Adding Profile for UMSI</h1>
  <?php
      if (isset($_SESSION['fail']))
      {
          echo('<p style="color: red;">' . htmlentities($_SESSION['fail']) . "</p>\n");
          unset($_SESSION['fail']);
      }
  ?>
    <form method="post">
        <p>First Name:
        <input type="text" name="first_name" size="60"/></p>
        <p>Last Name:
        <input type="text" name="last_name" size="60"/></p>
        <p>Email:
        <input type="text" name="email" size="30"/></p>
        <p>Headline:<br/>
        <input type="text" name="headline" size="80"/></p>
        <p>Summary:<br/>
        <textarea name="summary" rows="8" cols="80"></textarea>
        <p>
        Position: <input type="submit" id="addPos" value="+">
        <div id="position_fields">
        </div>
        </p>
        <p>
        <input type="submit" value="Add">
        <input type="submit" name="cancel" value="Cancel">
        </p>
    </form>
    <script>
        countPos = 0;
        // http://stackoverflow.com/questions/17650776/add-remove-html-inside-div-using-javascript
        $(document).ready(function ()
        {
            window.console && console.log('Document ready called');
            $('#addPos').click(function (event) {
                // http://api.jquery.com/event.preventdefault/
                event.preventDefault();
                if (countPos >= 9)
                {
                    alert("Maximum of nine position entries exceeded");
                    return;
                }
                countPos++;
                window.console && console.log("Adding position " + countPos);
                $('#position_fields').append(
                    '<div id="position' + countPos + '"> \
                <p>Year: <input type="text" name="year' + countPos + '" value="" /> \
                <input type="button" value="-" \
                    onclick="$(\'#position' + countPos + '\').remove();return false;"></p> \
                <textarea name="desc' + countPos + '" rows="8" cols="80"></textarea>\
                </div>');
                });
          });
      </script>
</div>
</body>
</html>
