<?php
    include 'func.php';
?>

<!doctype html>
<html>
    <head>
        <meta charset=utf-8>
        <title>Foxentry test</title>
    </head>
    <body>
        
        <?php
            if (!$_POST || $_POST['url'] == "" || $_POST['elementText'] == "" ) 
            {
        ?>
            <form method="post">
                <label>Vložte url</label>
                <input type = "text" name = "url" value="<?php echo $_POST['url']; ?>"><br>
                <label>Vložte text nadpisu</label>
                <input type = "text" value ="<?php echo $_POST['elementText']; ?>" name ="elementText"><br>
                <input type="submit" value = "Odeslat">
            </form>
        <?php
            }else{
               indexSite($_POST['url'], "h", $_POST['elementText']); 
               $data = getIndexData($_POST['url'], $_POST['elementText']);
        ?>
            <table>
                <tr>
                    <th>Titulek</th>
                    <th>Text</th>
                </tr>
                <?php
                foreach ($data as $row)
                {
                ?>
                    <tr>
                        <td><?php echo $row['header']; ?> </td>
                        <td><?php echo $row['text']; ?> </td>
                    </tr>
            <?php
                }
            ?>
            </table>
            <a href = "index.php">Návrat na formulář</a>
        <?php
            }
        ?>
    </body>
</html>
