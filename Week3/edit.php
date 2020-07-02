<?php

    require_once "pdo.php";
    require_once "util.php";
    session_start();
    if (!isset($_SESSION['name']))
    {
        die('Not logged in');
    }

    if (isset($_SESSION['cancel']))
    {
        header('Location: index.php');
        return;
    }

    if (isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) && isset($_POST['headline']) && isset($_POST['summary']) && isset($_POST['profile_id']))
    {
      if ( strlen($_POST['first_name']) < 1 || strlen($_POST['last_name']) < 1 || strlen($_POST['email']) < 1 || strlen($_POST['headline']) < 1 || strlen($_POST['summary']) < 1)
      {
         $_SESSION['error'] = 'All fields are required';
         header("Location: edit.php");
         return;
      }
      else if (strpos($_POST['email'], '@') == false)
      {
       $_SESSION['error'] = 'The mail must have an at (@) sign';
       header("Location: edit.php");
       return;
      }
      else
      {
        $msg = validatePos();
        if(is_string($msg))
        {
            $_SESSION['error'] = $msg;
            header("Location: edit.php?profile_id=".$_GET['profile_id']);
            return;
        }
        $sql = "UPDATE profile SET first_name = :fn, last_name = :ln,
                email = :em, headline = :he, summary = :su
                WHERE profile_id = :p_idmrk";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
          ':fn' => $_POST['first_name'],
          ':ln' => $_POST['last_name'],
          ':em' => $_POST['email'],
          ':he' => $_POST['headline'],
          ':su' => $_POST['summary'],
          ':p_idmrk' => $_POST['profile_id'])
        );
        $stmt = $pdo->prepare('DELETE FROM Position WHERE profile_id = :pid');
        $stmt->execute(array(
          ':pid' => $_GET['profile_id']
        ));

        $rank = 1;
        for($i=1; $i <= 9; $i++)
        {
            if ( ! isset($_POST['year'.$i])) continue;
            if ( ! isset($_POST['desc'.$i])) continue;
            $year = $_POST['year'.$i];
            $desc = $_POST['desc'.$i];

            $stmt = $pdo->prepare('INSERT INTO Position
            (profile_id, rank, year, description) VALUES (:pid, :rnk, :year, :descr)');
            $stmt->execute(array(
              ':pid' => $_GET['profile_id'],
              ':rnk' => $rank,
              ':year' => $year,
              ':descr' => $desc
            ));
            $rank++;
          }

          $_SESSION['success'] = 'Record updated';
          header( 'Location: index.php' ) ;
          return;
        }
      }

      $position = loadPos($pdo, $_GET['profile_id']);
      $stmt = $pdo->prepare("SELECT * FROM profile where profile_id = :xyz");
      $stmt->execute(array(":xyz" => $_GET['profile_id']));
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ( $row === false )
      {
        $_SESSION['error'] = 'Bad value for profile_id';
        header( 'Location: index.php' ) ;
        return;
      }

      $fn = htmlentities($row['first_name']);
      $ln = htmlentities($row['last_name']);
      $em = htmlentities($row['email']);
      $he = htmlentities($row['headline']);
      $su = htmlentities($row['summary']);
      $p_id = htmlentities($row['profile_id']);

?>



<!DOCTYPE html>
<html>
<head>
    <title>Ambika Patidar's Login Page</title>
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

    <?php require_once "bootstrap.php"; ?>

</head>
<body>
<div class="container">
    <h1>Editing Profile for UMSI</h1>
    <?php
    if (isset($_SESSION['error']))
    {
        echo('<p style="color: red;">' . htmlentities($_SESSION['error']) . "</p>\n");
        unset($_SESSION['error']);
    }
    ?>
    <form method="post">
        <p>First Name:
            <input type="text" name="first_name" size="60" value="<?php echo $row['first_name'] ?>"/></p>
        <p>Last Name:
            <input type="text" name="last_name" size="60" value="<?php echo $row['last_name'] ?>"/></p>
        <p>Email:
            <input type="text" name="email" size="30" value="<?php echo $row['email'] ?>"/></p>
        <p>Headline:<br/>
            <input type="text" name="headline" size="80" value="<?php echo $row['headline'] ?>"/></p>
        <p>Summary:<br/>
            <textarea name="summary" rows="8" cols="80"><?php echo $row['summary'] ?></textarea></p>
        <p>
            <input type="hidden" name="profile_id" value="<?= htmlentities($_GET['profile_id']); ?>"/></p>
        <p>
            Position: <input type="submit" id="addPos" value="+">
            <div id="position_fields">
                <?php
                $rank = 1;
                foreach ($rowOfPosition as $row)
                {
                    echo "<div id=\"position" . $rank . "\">
                    <p>Year: <input type=\"text\" name=\"year1\" value=\"".$row['year']."\">
                    <input type=\"button\" value=\"-\" onclick=\"$('#position". $rank ."').remove();return false;\"></p>
                    <textarea name=\"desc". $rank ."\"').\" rows=\"8\" cols=\"80\">".$row['description']."</textarea>
                    </div>";
                $rank++;
                } ?>
            </div>
            <input type="submit" value="Save">
            <input type="submit" name="cancel" value="Cancel">
        </p>
    </form>
    <script>
           countPos = 0;
           // http://stackoverflow.com/questions/17650776/add-remove-html-inside-div-using-javascript
           $(document).ready(function ()
           {
               window.console && console.log('Document ready called');
               $('#addPos').click(function (event)
               {
                   // http://api.jquery.com/event.preventdefault/
                   event.preventDefault();
                   if (countPos >= 9) {
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
