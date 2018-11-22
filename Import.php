<?php

require_once 'CLI.php';
$_SERVER['HTTP_USER_AGENT'] = '';
$_SERVER['PHP_SELF'] = 'index2.php';
$_SESSION ['auth'] = '';
include_once 'data.php';
include_once 'functions.php';
global $dbHandle;


class Import extends CLI
{
    function __construct($appname = null, $author = null, $copyright = null)
    {
        parent::__construct('CLI Framework Example', 'Author Name', '(c) 2012 Etopian Inc.');
    }

    private $path;

    /**
     * The main() function gets called if at least one argument is present.
     * If no arguments are present, the automatically generated help is displayed.
     *
     * The main functions job to do the main work of the script.
     */
    public function main()
    {


        if(!empty($this->path) && is_dir($this->path)) {
            $this->processFiles();
        }

    }

    private function titleExif($path, $title){

        $pdf_info = [];
        @exec('exiftool "' . $path . '"', $pdf_info);
        foreach ($pdf_info as $value) {
            $ar = explode(':', $value);
            if (strtolower(trim($ar[0])) == 'title') {
                $_title = trim($ar[1]);
                $_title = str_replace('.pdf', '', $_title);
                if (!is_numeric($_title) && strlen($_title) > 5) {
                    $title = $_title;
                }
            }
        }

        return $title;
    }


    private function getBook(SplFileInfo $file){
        $filename = $file->getBasename('.' . $file->getExtension());
        $filename = preg_replace("/\([^)]+\)/", "", $filename);
        $file_title = preg_replace('~[^\p{L}\p{N}]++~u', ' ', $filename);
        $path = $file->getRealPath();

        $title = $this->titleExif($path, $file_title);
        $input = false;

        $answer = false;
        while ($answer == false) {
            print 'Filename:' . $file_title . "\n----------------------\n";
            //return [];
            $json = $this->getBooks($title);
            if (isset($json->items)) {
                foreach ($json->items as $i => $item) {
                    $authors = @implode(', ', $item->volumeInfo->authors);
                    $date = @$item->volumeInfo->publishedDate;
                    $print_text = $item->volumeInfo->title . ' - ' . @$item->volumeInfo->subtitle . ' ' . $date . ' (' . $authors . ')';
                    $compare_text = $authors .' '.$item->volumeInfo->title ;
                    $similar = similar_text($file_title, $compare_text);
                    print $i.') '.$similar.' '.$print_text."\n";
                    if($similar > 33){
                        $input = 0;
                        $answer = true;
                    }
                }
            }


            print "o) Open item\n";
            print "f) Use filename\n";
            print "s) Skip importing\n";
            print "m) Manually define\n";
            if($input === false){
                $input = $this->getInput("Select an entry or enter a different title: ");
            }

            if($input == 's'){
                file_put_contents('./skipped.txt', $path."\n", FILE_APPEND);
                return [];
            }else if($input == 'o'){
                exec('open "'.$path.'"');
            }else if($input == 'f'){
                $title = $file_title;
            }else if (!is_numeric($input) && strlen($input) > 3) {
                $title = $input;
                continue;
            } else if (is_numeric($input)) {
                $answer = true;
            }
        }

        $item = $json->items[$input];
        foreach($item->volumeInfo->authors as $key => $author){
            $item->volumeInfo->authors[$key] = $this->migrate_authors($author);
        }
        $data['author'] = @implode(';', $item->volumeInfo->authors);
        $data['title'] = $item->volumeInfo->title;
        $data['secondary_title'] = @$item->volumeInfo->subtitle;
        $data['date'] = @$item->volumeInfo->publishedDate;
        $data['description'] = @$item->volumeInfo->description;
        $data['publisher'] = @$item->volumeInfo->publisher;
        $data['pages'] = @$item->volumeInfo->pageCount;

        return $data;
    }


    private function processFiles()
    {

        $items = [];
        foreach (new FilesystemIterator($this->path) as $file) {
            if(strtolower($file->getExtension()) != 'pdf') continue;
            $data = $this->getBook($file);
            if(!empty($data)){
                print_r($data);
                $this->indexfile($file->getRealPath(), $data);
            }
        }
    }

    private function getBooks($filename)
    {
        return json_decode(file_get_contents('https://www.googleapis.com/books/v1/volumes?q=' . urlencode($filename)));
    }
    private function indexfile($file_path, $data)
    {

        global $dbHandle;
        database_connect(IL_DATABASE_PATH, 'library');

        $file = $file_path;
        set_time_limit(600);
        $url = '';
        $authors = $data['author'];
        $affiliation = '';
        $title = $data['title'];
        $abstract = $data['description'];
        $secondary_title = $data['secondary_title'];
        $tertiary_title = '';
        $year = $data['date'];
        $volume = '';
        $issue = '';
        $pages = $data['pages'];
        $last_page = '';
        $journal_abbr = '';
        $keywords = '';
        $name_array = array();
        $mesh_array = array();
        $new_file = '';
        $addition_date = date('Y-m-d');
        $rating = 2;
        $uid = '';
        $editor = '';
        $reference_type = 'book';
        $publisher = $data['publisher'];
        $place_published = '';
        $doi = '';
        $authors_ascii = '';
        $title_ascii = '';
        $abstract_ascii = '';
        $unpacked_files = array();
        $temp_file = IL_TEMP_PATH . DIRECTORY_SEPARATOR . $user . "_librarian_temp" . $i . ".txt";
        $userID = 1;

        if (!empty($title)) {

            database_connect(IL_DATABASE_PATH, 'library');

            if (!empty($authors)) {
                $authors_ascii = utf8_deaccent($authors);
            }

            $title_ascii = utf8_deaccent($title);

            if (!empty($abstract)) {
                $abstract_ascii = utf8_deaccent($abstract);
            }

            // record publication data, table library    ##########

            $query = "INSERT INTO library (file, authors, affiliation, title, journal, year, addition_date, abstract, rating, uid, volume, issue, pages,
                        secondary_title, tertiary_title, editor,
                        url, reference_type, publisher, place_published, keywords, doi, authors_ascii, title_ascii, abstract_ascii, added_by)
                        VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :authors, :affiliation,
                        :title, :journal, :year, :addition_date, :abstract, :rating, :uid, :volume, :issue, :pages, :secondary_title, :tertiary_title, :editor,
                        :url, :reference_type, :publisher, :place_published, :keywords, :doi, :authors_ascii, :title_ascii, :abstract_ascii, :added_by)";

            $stmt = $dbHandle->prepare($query);

            $stmt->bindParam(':authors', $authors, PDO::PARAM_STR);
            $stmt->bindParam(':affiliation', $affiliation, PDO::PARAM_STR);
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':journal', $journal_abbr, PDO::PARAM_STR);
            $stmt->bindParam(':year', $year, PDO::PARAM_STR);
            $stmt->bindParam(':addition_date', $addition_date, PDO::PARAM_STR);
            $stmt->bindParam(':abstract', $abstract, PDO::PARAM_STR);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $stmt->bindParam(':uid', $uid, PDO::PARAM_STR);
            $stmt->bindParam(':volume', $volume, PDO::PARAM_STR);
            $stmt->bindParam(':issue', $issue, PDO::PARAM_STR);
            $stmt->bindParam(':pages', $pages, PDO::PARAM_STR);
            $stmt->bindParam(':secondary_title', $secondary_title, PDO::PARAM_STR);
            $stmt->bindParam(':tertiary_title', $tertiary_title, PDO::PARAM_STR);
            $stmt->bindParam(':editor', $editor, PDO::PARAM_STR);
            $stmt->bindParam(':url', $url, PDO::PARAM_STR);
            $stmt->bindParam(':reference_type', $reference_type, PDO::PARAM_STR);
            $stmt->bindParam(':publisher', $publisher, PDO::PARAM_STR);
            $stmt->bindParam(':place_published', $place_published, PDO::PARAM_STR);
            $stmt->bindParam(':keywords', $keywords, PDO::PARAM_STR);
            $stmt->bindParam(':doi', $doi, PDO::PARAM_STR);
            $stmt->bindParam(':authors_ascii', $authors_ascii, PDO::PARAM_STR);
            $stmt->bindParam(':title_ascii', $title_ascii, PDO::PARAM_STR);
            $stmt->bindParam(':abstract_ascii', $abstract_ascii, PDO::PARAM_STR);
            $stmt->bindParam(':added_by', $userID, PDO::PARAM_INT);

            $dbHandle->beginTransaction();

            $stmt->execute();
            $stmt = null;

            $id = $dbHandle->lastInsertId();
            $new_file = str_pad($id, 5, "0", STR_PAD_LEFT) . '.pdf';

            // Save citation key.
            $stmt6 = $dbHandle->prepare("UPDATE library SET bibtex=:bibtex WHERE id=:id");

            $stmt6->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);
            $stmt6->bindParam(':id', $id, PDO::PARAM_INT);

            $bibtex_author = 'unknown';

            if (!empty($last_name[0])) {
                $bibtex_author = utf8_deaccent($last_name[0]);
                $bibtex_author = str_replace(' ', '', $bibtex_author);
            }

            empty($year) ? $bibtex_year = '0000' : $bibtex_year = substr($year, 0, 4);

            $bibtex = $bibtex_author . '-' . $bibtex_year . '-ID' . $id;

            $insert = $stmt6->execute();
            $insert = null;

            if (isset($_GET['shelf']) && !empty($userID)) {
                $user_query = $dbHandle->quote($userID);
                $file_query = $dbHandle->quote($id);
                $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) VALUES ($user_query,$file_query)");
            }

            if (isset($_GET['project']) && !empty($_GET['projectID'])) {
                $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (" . intval($_GET['projectID']) . "," . intval($id) . ")");
            }

            // record new category into categories, if not exists #########

            if (!empty($_GET['category2'])) {

                $_GET['category2'] = preg_replace('/\s{2,}/', '', $_GET['category2']);
                $_GET['category2'] = preg_replace('/^\s$/', '', $_GET['category2']);
                $_GET['category2'] = array_filter($_GET['category2']);

                $query = "INSERT INTO categories (category) VALUES (:category)";
                $stmt = $dbHandle->prepare($query);
                $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

                while (list($key, $new_category) = each($_GET['category2'])) {
                    $new_category_quoted = $dbHandle->quote($new_category);
                    $result = $dbHandle->query("SELECT categoryID FROM categories WHERE category=$new_category_quoted");
                    $exists = $result->fetchColumn();
                    $category_ids[] = $exists;
                    $result = null;
                    if (empty($exists)) {
                        $stmt->execute();
                        $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM categories");
                        $category_ids[] = $last_id->fetchColumn();
                        $last_id = null;
                    }
                }
                $stmt = null;
            }

            // record new relations into filescategories #########

            $categories = array();

            if (!empty($_GET['category']) || !empty($category_ids)) {
                $categories = array_merge((array) $_GET['category'], (array) $category_ids);
                $categories = array_filter(array_unique($categories));
            }

            $query = "INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES (:fileid,:categoryid)";

            $stmt = $dbHandle->prepare($query);
            $stmt->bindParam(':fileid', $id);
            $stmt->bindParam(':categoryid', $category_id);

            while (list($key, $category_id) = each($categories)) {
                if (!empty($id)) {
                    $stmt->execute();
                }
            }
            $stmt = null;

            $dbHandle->commit();

            copy($file, IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_PDF_PATH) . DIRECTORY_SEPARATOR . $new_file);

            $hash = md5_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file);

            //RECORD FILE HASH FOR DUPLICATE DETECTION
            if (!empty($hash)) {
                $hash = $dbHandle->quote($hash);
                $dbHandle->exec('UPDATE library SET filehash=' . $hash . ' WHERE id=' . $id);
            }

            $dbHandle = null;

            recordFulltext($id, $new_file);

        }

    }


    public function option_path($opt = null)
    {
        if ($opt == 'help') {
            return 'Path to directory to scan, --path=/var/pdf';
        }

        $this->path = $opt;

    }
    function migrate_authors($string)
    {
        $result = '';
        $array = array();
        $new_authors = array();
        $string = str_ireplace(' and ', ' , ', $string);
        $string = str_ireplace(', and ', ' , ', $string);
        $string = str_ireplace(',and ', ' , ', $string);
        $string = str_ireplace(';', ',', $string);
        $array = explode(',', $string);
        $array = array_filter($array);
        if (!empty($array)) {
            foreach ($array as $author) {
                $author = trim($author);
                $author = str_replace('"', '', $author);
                $space = strpos($author, ' ');
                if ($space === false) {
                    $last = trim($author);
                    $first = '';
                } else {
                    $last = trim(substr($author, 0, $space));
                    $first = trim(substr($author, $space + 1));
                }
                if (!empty($last))
                    $new_authors[] = 'L:"' . $last . '",F:"' . $first . '"';
            }
            if (count($new_authors) > 0)
                $result = join(';', $new_authors);
        }
        return $result;
    }

}

new Import();
