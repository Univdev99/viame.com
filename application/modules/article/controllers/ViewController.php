<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Article_ViewController extends ViaMe_Controller_Default_View
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    /*
        Most Done in the Default
        
        library/ViaMe/Controller/Default/View
     */
     
    public function indexAction()
    {
        parent::indexAction();
        
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true; // Turn on the header
        $this->internal->sublayout_with_footer = true; // Turn on the footer
        
        // Similar Content - Could change _tableName to module_template for all content modules
        $clauses = array();
        foreach (array('com_id', 'net_id', 'via_id', 'module_id', 'matrix_counter', 'counter') as $clause) {
            $clauses[] = $this->db->quoteInto("$clause=?", $this->view->object->{$clause});
        }
        $whereClause2 = implode(' AND ', $clauses);
        foreach ($clauses as $key => $val) {
            $clauses[$key] = 'obj.' . $val;
        }
        $whereClause1 = implode(' AND ', $clauses);
        
        $nullObj = new StdClass;
        $nullObj->overrideSpace = true;
        $select = $this->_buildComplexQuery($this->_tableName, $nullObj);
        $select->join(array('z' => new Zend_Db_Expr("(SELECT tsquery FROM (SELECT to_tsquery(array_to_string(array_accum(words.word), ' | ')) AS tsquery FROM (SELECT word FROM ts_stat('SELECT COALESCE(NULLIF(to_tsvector(meta_keywords), ''''), NULLIF(to_tsvector(title), ''''), NULLIF(to_tsvector(heading), ''''), NULLIF(to_tsvector(summary), ''''), NULLIF(to_tsvector(meta_description), ''''), search) FROM article_articles WHERE " . preg_replace("/^'(.*?)'$/", "\\1", $this->db->quote($whereClause2)) . "') WHERE word !~ E'^\\\d+$' ORDER BY nentry DESC, ndoc DESC, word limit 5) AS words) AS query)")), 'true', array())
            ->where($this->db->quoteInto('obj.profile_id <> ?', $this->view->object->profile_id) . " AND NOT ($whereClause1) AND search @@ z.tsquery")
            #->where("obj.creation >= ('now'::timestamp - '1 year'::interval)")
            ->where('(obj.com_id=? OR p.community_id=?)', $this->community->id)
            ->order("ts_rank_cd('{.2, .6, .8, 1}', obj.search, z.tsquery, 4) DESC")
            ->limit(10);
            
        #$select
        #    ->where($this->db->quoteInto('obj.profile_id <> ?', $this->view->object->profile_id) . " AND NOT ($whereClause1) AND search @@ (SELECT to_tsquery(array_to_string(array_accum(word), ' | ')) FROM (SELECT word FROM ts_stat('SELECT search FROM " . $this->_tableName . " WHERE " . preg_replace("/^'(.*?)'$/", "\\1", $this->db->quote($whereClause2)) . "', 'ab') WHERE word !~ E'^\\d+$' ORDER BY nentry DESC, ndoc DESC, word LIMIT 20) AS words)")
        #    ->order('obj.creation DESC')
        #    ->limit(10);
        #echo $select;
        
        $this->view->similar_content = $this->db->fetchAll($select);
        
        // Other Content By This Author
        $select = $this->_buildComplexQuery($this->_tableName, $nullObj);
        $select
            ->where("NOT ($whereClause1) AND obj.profile_id=?", $this->view->object->profile_id)
            ->order('published_display_date DESC')
            ->limit(10);
        
        $this->view->author_content = $this->db->fetchAll($select);
        
        #if ($this->_getParam('newview')) { $this->render('index2'); }
    }
}