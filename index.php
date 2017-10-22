
<form action="" method="post">
    <p>Import song titles <button name="titles" value="titles">Upload</button></p>
    <p>Import verses <button name="verses" value="verses">Upload</button></p>
</form>

<?php
error_reporting(E_ALL & ~E_NOTICE);
include './simple_html_dom.php';

$db = new MyDB();

if (!empty($_POST['titles'])) {

    $has_audio = array(178, 180, 182, 184, 185, 187, 188, 191, 192, 193, 195, 196, 197, 198, 199, 
        200, 1, 2, 3, 4, 5, 7, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 22, 25, 26, 29, 30, 32,
        33, 34, 35, 36, 37, 38, 40, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 58, 60,
        61, 63, 65, 66, 67, 69, 70, 73, 74, 75, 91, 93, 97, 98, 101, 103, 104, 105, 108, 109, 110, 
        111, 113, 114, 115, 117, 118, 119, 120, 122, 123, 124, 126, 127, 128, 129, 130, 131, 132,
        133, 134, 135, 137, 138, 139, 140, 142, 144, 145, 146, 147, 149, 151, 152, 153, 155, 156,
        166, 168, 173, 175, 176, 177);
    
    $db->exec("DROP TABLE IF EXISTS songs");
    $sql = 'CREATE TABLE "songs" (
  "title_no" INTEGER PRIMARY KEY NOT NULL,
  "title" TEXT NOT NULL,
  "has_audio" TINYINT DEFAULT 0)';

    $verseQ = "";
    $db->exec($sql);
    $count = 1;

    echo "Importing song titles <br/>";

    $html = file_get_html("C:/xampp/htdocs/nzkextractor/index.html");

    foreach ($html->find('tr') as $tr) {
        $title = $tr->find('td', 1)->plaintext;
        $title_no = $count;
        $title = trim($title);

//        echo $title_no . " " . $title . "<br/>";
        $verseQ = $verseQ . '("' . $title_no . '","' . $title . '",' . (in_array($count, $has_audio) ? 1 : 0) . '),';
        $count++;
    }

    $build_titles = "INSERT INTO songs (title_no, title, has_audio) VALUES " . substr(trim($verseQ), 0, -1);

    $db->exec($build_titles);

    echo "<br /> Import song titles finished";
}

if (!empty($_POST['verses'])) {

    $db->exec("DROP TABLE IF EXISTS verses");
    $sql = 'CREATE TABLE "verses" (
  "id" INTEGER ZEROFILL PRIMARY KEY NOT NULL,
  "title_no" integer NOT NULL,
  "verse_no" integer NOT NULL,
  "verse_text" text NOT NULL)';

    $db->exec($sql);

    echo "Import verses to SQLite db <br/>";

    $path = realpath('C:/xampp/htdocs/nzkextractor/tenzi');
    $iterator = new DirectoryIterator($path);
//    $iterator->setFlags(DirectoryIterator::SKIP_DOTS);
    $objects = new IteratorIterator($iterator);

    $part = "";

    foreach ($objects as $name) {
        if ($name->isFile()) {

            $title_no = filter_var($name, FILTER_SANITIZE_NUMBER_INT);

            $file_path = $path . '\\' . $name;

            echo '<br/>';
            echo $file_path;
            echo '<br/>';
            echo '<br/>';

            $html = file_get_html($file_path);
            $index = 1;

            foreach ($html->find('.tenzi', 0)->find('p') as $element) {

                // remove unwanted html tags
//                foreach ($html->find('a') as $removed) {
//                    $removed->outertext = '';
//                }

                $verse_text = str_get_html($element->innertext);
                $verse_text = trim($verse_text);
                $verse_text = preg_replace("/^(\d+)./m", '<font color="#FF6F00">$1. </font>', $verse_text);

                $verse_text = str_replace("<em>", '<em><font color="#997300">', $verse_text);
                $verse_text = str_replace('</em>', '</font></em>', $verse_text);
//                $verse_text = SQLite3::escapeString($verse_text);

                echo $verse_text;
                echo '<br/>';
                echo '<br/>';

                if (!empty($verse_text)) {

                    $verse_no = $index;
                    $verse_id = $title_no . str_pad($verse_no, 3, "0", STR_PAD_LEFT);

//                    $part = $part . '(' . $verse_id . ', ' . $title_no . ', ' . $verse_no . ', "' . $verse_text . '"),';
                    $part = $part . "({$verse_id}, {$title_no}, {$verse_no}, '{$verse_text}'),";
                    $index++;
                }
            }
        }
    }

    $build_query = 'INSERT INTO verses (id, title_no, verse_no, verse_text) VALUES ' . substr(trim($part), 0, -1);
//    echo $build_query;
//    exit;
//    $build_query = $db->escapeString($build_query);
    $db->exec($build_query);

    echo "Importing verses finished";
}

class MyDB extends SQLite3 {

    function __construct() {
        $this->open('tenzi.db');
    }

}
?>
