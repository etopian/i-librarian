<?php
include_once 'CLI.php';


class Import extends CLI {
    function __construct($appname = null, $author = null, $copyright = null) {
        parent::__construct('CLI Framework Example', 'Author Name', '(c) 2012 Etopian Inc.');
      }
    
      private $path;
      
      /**
       * The main() function gets called if at least one argument is present.
       * If no arguments are present, the automatically generated help is displayed.
       *
       * The main functions job to do the main work of the script.
       * 
       */
      public function main(){
    

        if(!empty($this->path) && is_dir($this->path)){
          $this->indexfiles();
        }
        
      }

      private function indexfiles(){

        $i = 0;

        foreach(new FilesystemIterator($this->path) as $file){

          $filename = $file->getBasename('.'.$file->getExtension());
          $filename = preg_replace('~[^\p{L}\p{N}]++~u', ' ', $filename);
          
          $json = json_decode(file_get_contents('https://www.googleapis.com/books/v1/volumes?q='.urlencode($filename)));
          if($book = array_shift($json->items)){
            print_r($book);
          }
          exit();
            set_time_limit(600);
    
            $i = $i + 1;
    
    
                $string = '';
                $record = '';
                $count = '';
                $url = '';
                $authors = '';
                $affiliation = '';
                $title = '';
                $abstract = '';
                $secondary_title = '';
                $tertiary_title = '';
                $year = '';
                $volume = '';
                $issue = '';
                $pages = '';
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
                $publisher = '';
                $place_published = '';
                $doi = '';
                $authors_ascii = '';
                $title_ascii = '';
                $abstract_ascii = '';
                $unpacked_files = array();
                $temp_file = IL_TEMP_PATH . DIRECTORY_SEPARATOR . $user . "_librarian_temp" . $i . ".txt";
                $userID = 1;
        
                ##########	extract text from pdf	##########
    
                system(select_pdftotext() . ' -enc UTF-8 -f 1 -l 3 ' . escapeshellarg($file) . ' ' . escapeshellarg($temp_file));

             
    
                    $string = str_replace($order, ' ', $string);
                    $order = array("\xe2\x80\x93", "\xe2\x80\x94");
                    $replace = '-';
                    $string = str_replace($order, $replace, $string);
    
                    preg_match_all('/10\.\d{4}\/\S+/ui', $string, $doi, PREG_PATTERN_ORDER);

  
                      if (!empty($title)) {
  
                          database_connect(IL_DATABASE_PATH, 'library');
  
                          if (!empty($authors))
                              $authors_ascii = utf8_deaccent($authors);
  
                          $title_ascii = utf8_deaccent($title);
  
                          if (!empty($abstract))
                              $abstract_ascii = utf8_deaccent($abstract);
  
                          ##########	record publication data, table library	##########
  
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
  
                          ####### record new category into categories, if not exists #########
  
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
  
                          ####### record new relations into filescategories #########
  
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
                              if (!empty($id))
                                  $stmt->execute();
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
                    //}
                
            }

      }
      
      
      /**
       * Now we define flags, arguments and options.
       * Notice each one of the defintions for these functions must be public.
       *
       *  The basic naming convention is:
       *    public function flag_NAME
       *    public function option_NAME
       *    public function argument_NAME
       *
       * In the function we define the help using:
       *  if($opt == 'help'){  return 'help message'. }
       *
       * We then follow up code to process that argument, flag, or option.
       *
       * There are no return values expected from any of the following functions.
       * 
       */
      
      
      /**
       * Define the flag -e, so if you run './example -e' this function will be called
       * Flags do not handle values, to handle values ($opt) use option_ for that.
       */
      public function flag_e($opt = null){
        if($opt == 'help'){
          return 'Help for the flag -e';
        }
        print "\n".'flag_e was just called and $opt was: '.$opt."\n";
        $this->flagvar2 = 'flag var is now set';
      }

      public function flag_p($opt = null){
        if($opt == 'help'){
          return 'Help for the flag -e';
        }

      }
      
      /**
       * Argument is like flag, but just a string.
       * ./example.php example
       */
      public function argument_example($opt = null){
        if($opt == 'help'){
          return 'Help for the argument \'example\'';
        }
        
        print "\n".'argument_example was just called and $opt was: '.$opt."\n";
        $this->argumentvar = 'example';
    
      }

      private function scandir($opt = null){
    
        if(!empty($this->path)){
          foreach(new FilesystemIterator($this->path) as $file){
            print_r($file);
          }
        }


      }
      
      /**
       * ./example.php --example=test
       *
       * Will output $opt = test when this function is called.
       *  
       */
      public function option_example($opt = null){    
        if($opt == 'help'){
          return 'Help for the option --example=value';
        }
        print "\n".'option_example was just called and $opt was: '.$opt."\n";
        $this->optionvar1 = $opt;
        
        //you can also call $this->getInput('message');
        //from within these functions to get input associated with the given option.
      } 

      public function option_path($opt = null){
        if($opt == 'help'){
          return 'Path to directory to scan, --path=/var/pdf';
        }

        $this->path = $opt;

      }
    
}

new Import();