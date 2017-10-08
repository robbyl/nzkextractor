
<form action="" method="post">
    <p>Import song titles <button name="titles" value="titles">Upload</button></p>
    <p>Import verses <button name="verses" value="verses">Upload</button></p>
</form>

<?php
error_reporting(E_ALL & ~E_NOTICE);
include './simple_html_dom.php';

$db = new MyDB();

if (!empty($_POST['titles'])) {

//    $has_audio = array(1, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 15, 17, 18, 20, 22, 23, 24, 29, 31, 32, 33, 36, 37, 38, 39, 40, 42, 45, 46,
//        50, 51, 54, 55, 57, 58, 60, 61, 65, 66, 67, 69, 70, 71, 72, 73, 77, 78, 79, 80, 81, 82, 83, 84, 86, 87, 89, 93, 95, 96, 97,
//        101, 103, 104, 107, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 125, 126, 127, 128, 130, 131, 133, 135, 136, 137, 140,
//        141, 142, 143, 144, 145, 146, 149);
    
    $has_audio = array();

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

                $verse_text = str_replace("<em>", '<em><font color="#2196F3">', $verse_text);
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
