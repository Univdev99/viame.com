<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

require_once 'ViaMe/Vm/Quotes/Adapters/Yahoo.php';

class ViaMe_Vm_Quotes
{
    protected static $_adapter = null;
    
    // Symbol, Last, Last Date, Last Time, Change, Open, High, Low, Volume
    protected static $_fieldFormat = 'sl1d1t1c1ohgv';
    
    public static function setFieldFormat($format)
    {
        self::$_fieldFormat = $format;
    }
    
    
    public static function getFieldFormat()
    {
        return self::$_fieldFormat;
    }
    
    public static function setAdapter($adapter)
    {
        self::$_adapter = $adapter;
    }


    public static function getAdapter()
    {
        if (!self::$_adapter) {
            require_once 'ViaMe/Vm/Quotes/Adapters/Yahoo.php';
            self::$_adapter = new ViaMe_Vm_Quotes_Adapters_Yahoo();
        }

        return self::$_adapter;
    }
    
    
    public static function fetch($symbols = null, $format = null)
    {
        if ($format) { self::setFieldFormat($format); }
            
        if (Zend_Registry::isRegistered('db')) {
            $db = Zend_Registry::get('db');
            
            if (Zend_Registry::isRegistered('log')) {
                $log = Zend_Registry::get('log');
            }
            
            if (Zend_Registry::isRegistered('config')) {
                $config = Zend_Registry::get('config');
            }
            
            if (is_string($symbols) && strlen($symbols)) { $symbols = array($symbols); }
            if (is_array($symbols) && count($symbols)) {
                
                // Create Two Revolution Cycle
                $need_to_cycle_again = true;
                for ($cycle = 0; $cycle < 2 && $need_to_cycle_again; $cycle++) {
                    $need_to_cycle_again = false;
                    
                    // Allows optional usage of extension for those that do not need extension
                    foreach (preg_replace(array('/^\^/', '/(\.|\=).*/'), '', $symbols) as $temp) {
                        $wheres[] = $db->quoteInto('symbol=?', strtoupper($temp));
                    }
                    $query = "SELECT * FROM quote_view_symbol_matrix WHERE " . implode(' OR ', $wheres);
                    
                    #$query = "SELECT * FROM quote_view_symbol_matrix WHERE symbol IN (SELECT DISTINCT UPPER(list_array('{\"" . implode('", "', preg_replace(array('/^\^/', '/(\.|\=).*/'), '', $symbols)) . "\"}'::varchar[16][])))";
                    #$query = "SELECT * FROM quote_view_symbol_matrix WHERE symbol IN (SELECT DISTINCT UPPER(list_array('{\"" . implode('", "', $symbols) . "\"}'::varchar[16][])))";
                    
                    $stmt = $db->query($query);
                    $db_results = $stmt->fetchAll();
                    $data_by_id = $data_internal_symbols = $data_delayed_symbols = $changed_symbol = $return_pointer = array();
                    foreach ($db_results as $line) {
                        $data_by_id[(string) $line->id] = $line;
                        if (!isset($data_internal_symbols[$line->internal_symbol])) {
                            $data_internal_symbols[$line->internal_symbol] = $line->id;
                        }
                        if (!isset($data_delayed_symbols[$line->delayed_symbol])) {
                            $data_delayed_symbols[$line->delayed_symbol] = $line->id;
                        }
                    }
                    
                    for ($i = 0; $i < count($symbols); $i++) {
                        $symbol = strtoupper($symbols[$i]);
                        if (array_key_exists($symbol, $data_internal_symbols) || array_key_exists($symbol, $data_delayed_symbols)) {
                            if (array_key_exists($symbol, $data_delayed_symbols)) {
                                $changed_symbol[$i] = false;
                                $return_pointer[(string) $i] = $data_delayed_symbols[$symbol];
                            }
                            elseif (array_key_exists($symbol, $data_internal_symbols) &&
                                ($symbol != $data_by_id[(string) $data_internal_symbols[$symbol]]->delayed_symbol)) {
                                $symbols[$i] = $data_by_id[(string) $data_internal_symbols[$symbol]]->delayed_symbol;
                                
                                $changed_symbol[$i] = $symbol;
                                $return_pointer[(string) $i] = $data_internal_symbols[$symbol];
                            }
                            else {
                                $changed_symbol[$i] = false;
                                $return_pointer[(string) $i] = $data_delayed_symbols[$symbol];
                            }
                        }
                        else {
                            $return_pointer[(string) $i] = false;
                            $need_to_cycle_again = true;
                            self::lookup($symbols[$i]);
                        }
                    }
                }
                
            }
            
            $data_prefetch = array(
                'primary' => array(
                    'e1' => 'error',
                    's' => 'symbol',
                    'n' => 'name',
                    'x' => 'exchange'
                ),
                'data' => array(
                    'l1' => 'last',
                    'k3' => 'last_size',
                    'h' => 'day_high',
                    'g' => 'day_low',
                    'v' => 'volume',
                    'a2' => 'average_daily_volume',
                    'b' => 'bid',
                    'b6' => 'bid_size',
                    'a' => 'ask',
                    'a5' => 'ask_size',
                    'p' => 'previous_close',
                    'o' => 'open',
                    
                    'k' => 'year_high',
                    'j' => 'year_low',
                    
                    'e' => 'earnings_share',
                    'r' => 'pe_ratio',
                    's7' => 'short_ratio',
                    'd' => 'dividend_share',
                    'y' => 'dividend_yield',
                    'f6' => 'stock_float',
                    'j1' => 'market_cap',
                    
                    'e7' => 'eps_est_current_year',
                    'e8' => 'eps_est_next_year',
                    'e9' => 'eps_est_next_quarter',
                    'r6' => 'price_eps_est_current_year',
                    'r7' => 'price_eps_est_next_year',
                    'r5' => 'peg_ratio',
                    'b4' => 'book_value',
                    'p6' => 'price_book',
                    'p5' => 'price_sales',
                    'j4' => 'ebitda',
                    'm3' => 'ma_50',
                    'm4' => 'ma_200'
                )
            );
            #self::setFieldFormat('e1snx' . self::getFieldFormat());
            self::setFieldFormat(implode('', array_keys($data_prefetch['primary'])) . implode('', array_keys($data_prefetch['data'])) . self::getFieldFormat());
        }
        
        $results = self::getAdapter()->fetch($symbols, self::getFieldFormat());
        
        if (isset($db)) {
            // Fixup Data
            $fixup_fields = array('s', 'n');
            $fields_array = preg_split('/\s+/', preg_replace(array('/(\d+)/', '/([^\d\s])/'), array('${1} ', ' ${1}'), $format), -1, PREG_SPLIT_NO_EMPTY);
            $to_fix = array_intersect($fixup_fields, $fields_array);
            for ($i = 0; $i < count($results); $i++) {
                @list($error, $symbol, $name, $exchange) = array_splice($results[$i], 0, 4);
                $data_items = array();
                $counter = 0;
                foreach ($data_prefetch['data'] as $key => $val) {
                    $temp_array = $temp = null;
                    $temp_array = array_splice($results[$i], 0, 1);
                    if (isset($temp_array[0])) {
                        $temp = $temp_array[0];
                    }
                    
                    if (($key == 'j1' || $key == 'j4') && $temp != 'N/A') {
                        // Fixup Market Cap and EBITDA
                        $multiplier = 1;
                        
                        if (preg_match('/[a-z]$/i', $temp)) {
                            switch (strtoupper(substr($temp, -1))) {
                                case 'K':
                                    $multiplier = 1000;
                                    break;
                                case 'M':
                                    $multiplier = 1000000;
                                    break;
                                case 'B':
                                    $multiplier = 1000000000;
                                    break;
                                case 'T':
                                    $multiplier = 1000000000000;
                                    break;
                                case 'Q':
                                    $multiplier = 1000000000000000;
                                    break;
                            }
                        }
                        
                        #echo "\n$temp\n";
                        $temp = round($temp) * $multiplier;
                        #echo "\n$temp\n";
                    }
                    
                    $data_items[$val] = ($temp && $temp != 'N/A' ? new Zend_Db_Expr($temp) : null); // Treating Zeros as NULL
                    $counter++;
                }
                
                if (isset($return_pointer[(string) $i]) && $return_pointer[(string) $i]) {
                    if ($to_fix) {
                        for ($j = 0; $j < count($fields_array); $j++) {
                            if ($fields_array[$j] == 's' && $changed_symbol[$i]) {
                                $results[$i][$j] = $changed_symbol[$i];
                            }
                            elseif ($fields_array[$j] == 'n') {
                                $results[$i][$j] = $data_by_id[(string) $return_pointer[(string) $i]]->name;
                            }
                        }
                    }
                    
                    // UPSERT DATA - SYNC with the default data prefixed(see above)
                    // Update to MERGE command when available in postgres - NOT NECESSARY
                    if (!isset($data_by_id[(string) $return_pointer[(string) $i]]->seconds_since_data_updated)) {
                        // INSERT - New Data
                        $data_items['symbol_id'] = $return_pointer[(string) $i];
                        try {
                            $db->insert('quote_data', $data_items);
                        } catch (Exception $e) {
                            if (isset($log)) { $log->err('Insert new data into quote_data from fetch - symbol id #' . $return_pointer[(string) $i]); }
                        }
                        
                    }
                    elseif ($data_by_id[(string) $return_pointer[(string) $i]]->seconds_since_data_updated > (isset($config) && isset($config->quote->data_refresh)  ? $config->quote->data_refresh : 900)) {
                        // UPDATE - Existing Data
                        try {
                            $db->update('quote_data', $data_items, $db->quoteInto('symbol_id=?', $return_pointer[(string) $i]));
                        } catch (Exception $e) {
                            # Too many log messages
                            #if (isset($log)) { $log->err('Update data in quote_data from fetch - symbol id #' . $return_pointer[(string) $i]); }
                        }
                    }
                }
                elseif ($error == 'N/A') {
                    // New Symbol
                    self::lookup($symbol);
                    self::lookup($name);
                    
                    $symbol = strtoupper($symbol);
                    
                    $ins_symbol = preg_replace(array('/^\^/', '/(\.|\=).*/'), '', $symbol);
                    $ins_ext = preg_replace('/^[^\.\=]*/', '', $symbol);
                    $ins_type = 'Z';
                    $ins_exch = 'Unknown';
                    
                    if ($exchange) {
                        $ins_exch = $exchange;
                    }
                    
                    if ($ins_ext == '=X') {
                        $ins_type = 'C';
                        $ins_exch = 'CCY';
                    }
                    elseif ($ins_ext == '.X') {
                        $ins_type = 'O';
                    }
                    elseif ($ins_ext == '.CME' || $ins_ext == '.NYM' || $ins_ext == '.NYB' || $ins_ext == '.CMX' || $ins_ext == '.CBT') {
                        $ins_type = 'F';
                    }
                    
                    try {
                        $db->query('INSERT INTO quote_symbols (symbol, name, type_id, exchange_id) SELECT ?, ?, qt.id, qe.id FROM quote_types AS qt, quote_exchanges AS qe WHERE qt.type=? AND (qe.exchange=? OR qe.code=?)', array(
                            $ins_symbol,
                            $name,
                            $ins_type,
                            $ins_exch,
                            $ins_exch
                        ));
                    } catch (Exception $e) {
                        #if (isset($log)) { $log->err("Insert new symbol into quote_symbols from fetch ($ins_symbol)"); }
                    }
                    
                    #Zend_Debug::Dump(array($symbol, $ins_symbol, $ins_ext, $ins_type, $exchange));
                }
            }
        }
        
        return $results;
    }
    
    
    public static function fetchRT($symbols = null)
    {
        if (is_string($symbols) && strlen($symbols)) { $symbols = array($symbols); }
            
        // Checks done - begin
        if (is_array($symbols) && count($symbols)) {
            $old_adapter = null;
            if (!(self::getAdapter() instanceof ViaMe_Vm_Quotes_Adapters_Google)) {
                require_once 'ViaMe/Vm/Quotes/Adapters/Google.php';
                $old_adapter = self::getAdapter();
                self::setAdapter(new ViaMe_Vm_Quotes_Adapters_Google());
            }
            
            $results = self::getAdapter()->fetch($symbols);
            
            if ($old_adapter) { self::setAdapter($old_adapter); }
            
            return $results;
        }
    }
    
    
    public static function fetchDelayedById($ids = null)
    {
        if ($ids && Zend_Registry::isRegistered('db')) {
            $db = Zend_Registry::get('db');
            
            if (Zend_Registry::isRegistered('log')) {
                $log = Zend_Registry::get('log');
            }
            
            if (Zend_Registry::isRegistered('config')) {
                $config = Zend_Registry::get('config');
            }
            
            if (is_string($ids) && strlen($ids)) { $ids = array($ids); }
            
            // Checks done - begin
            if (is_array($ids) && count($ids)) {
                $results = self::lookupById($ids);
                
                // Any data to refresh in the database?
                $symbols_refresh = array();
                foreach ($results as $temp) {
                    if ($temp->seconds_since_data_updated > $config->quote->data_refresh) {
                        $symbols_refresh[] = $temp->delayed_symbol;
                    }
                }
                if (count($symbols_refresh)) { self::fetch($symbols_refresh, 'sl1ba'); }
                
                # Load Data From Database
                foreach ($ids as $id) { $where[] = $db->quoteInto('id=?', $id); }
                $quote_data = array();
                #$query = "SELECT * FROM quote_view_data WHERE (" . implode(' OR ', $where) . ") AND active='t'";
                $query = "SELECT * FROM quote_view_data WHERE (" . implode(' OR ', $where) . ")";
                foreach ($db->fetchAll($query) as $temp) {
                    $quote_data[$temp->symbol_id] = $temp;
                }
                
                return $quote_data;
            }
        }
    }
    
    
    public static function lookup($search = null)
    {
        $results = self::getAdapter()->lookup($search);

        for ($i = 0; $i < count($results); $i++) {
            if ($results[$i]['type']) { $types[$results[$i]['type']] = (isset($results[$i]['typeDisp']) ? $results[$i]['typeDisp'] : ''); }
            if ($results[$i]['exch']) { $exchs[$results[$i]['exch']] = (isset($results[$i]['exchDisp']) ? $results[$i]['exchDisp'] : ''); }
            if ($results[$i]['symbol']) {
                $symbol = $results[$i]['symbol'];
                $symbol = preg_replace(array('/^\^/', '/(\.|\=).*/'), '', $symbol);
                $symbols[$symbol] = $results[$i];
                
                $dcss[] = $symbol;
                $dcqt[] = $results[$i]['type'];
                $dcqe[] = $results[$i]['exch'];
                $dbna[$symbol . '-' . $results[$i]['type'] . '-' . $results[$i]['exch']] = $results[$i]['name'];
                if ($results[$i]['type'] == 'I') {
                    $dbos[$symbol . '-' . $results[$i]['type'] . '-' . $results[$i]['exch']] = $results[$i]['symbol'];
                    if (!preg_match('/^\^/', $results[$i]['symbol'])) {
                        $results[$i]['symbol'] = '^' . $results[$i]['symbol'];
                    }
                }
            }
        }

        
        if ($results && Zend_Registry::isRegistered('db')) {
            $db = Zend_Registry::get('db');
            
            // Update Types Table
            $query = "SELECT list_array('{\"" . implode('", "', array_keys($types)) . "\"}'::varchar[8][]) EXCEPT SELECT type FROM quote_types";
            foreach ($db->fetchCol($query) as $type) {
                try {
                    $db->insert('quote_types', array('type' => $type, 'display' => (isset($types[$type]) ? $types[$type] : ''), 'display_plural' => (isset($types[$type]) ? $types[$type] : '')));
                } catch (Exception $e) {
                    if (isset($log)) { $log->err("Insert new type into quote_types ($type) from lookup"); }
                }
            }
            
            // Update Exchanges Table
            $query = "SELECT list_array('{\"" . implode('", "', array_keys($exchs)) . "\"}'::varchar[8][]) EXCEPT SELECT code FROM quote_exchanges";
            foreach ($db->fetchCol($query) as $exch) {
                try {
                    $db->insert('quote_exchanges', array('code' => $exch, 'exchange' => (isset($exchs[$exch]) ? $exchs[$exch] : '')));
                } catch (Exception $e) {
                    if (isset($log)) { $log->err("Insert new exchange into quote_exchanges ($exch) from lookup"); }
                }
            }
            
            // Update Symbols Table
            $query = "SELECT list_array('{\"" . implode('", "', array_values($dcss)) . "\"}'::varchar[16][]), list_array('{\"" . implode('", "', array_values($dcqt)) . "\"}'::varchar[8][]), list_array('{\"" . implode('", "', array_values($dcqe)) . "\"}'::varchar[8][]) EXCEPT SELECT qs.symbol, qt.type, qe.code FROM quote_symbols AS qs, quote_types AS qt, quote_exchanges AS qe WHERE qs.type_id=qt.id AND qs.exchange_id=qe.id";
            $db->setFetchMode(Zend_Db::FETCH_NUM);
            $stmt = $db->query($query);
            while ($row = $stmt->fetch()) {
                try {
                    $db->query('INSERT INTO quote_symbols (symbol, name, delayed_symbol_override, realtime_symbol_override, type_id, exchange_id) SELECT UPPER(?), ?, ?, ?, qt.id, qe.id FROM quote_types AS qt, quote_exchanges AS qe WHERE qt.type=? AND qe.code=?', array(
                        $row[0],
                        $dbna[$row[0] . '-' . $row[1] . '-' . $row[2]],
                        (isset($dbos[$row[0] . '-' . $row[1] . '-' . $row[2]]) ? $dbos[$row[0] . '-' . $row[1] . '-' . $row[2]] : null),
                        (isset($dbos[$row[0] . '-' . $row[1] . '-' . $row[2]]) ? $dbos[$row[0] . '-' . $row[1] . '-' . $row[2]] : null),
                        $row[1],
                        $row[2]
                    ));
                } catch (Exception $e) {
                    if (isset($log)) { $log->err("Insert new symbol into quote_symbols ($row[0]) from lookup"); }
                }
            }
            $db->setFetchMode(Zend_Db::FETCH_OBJ);
        }
        
        return $results;
    }
    
    
    public static function verify($symbols = null)
    {
        $found = array();
        
        if (Zend_Registry::isRegistered('db')) {
            $db = Zend_Registry::get('db');
            
            if (Zend_Registry::isRegistered('log')) {
                $log = Zend_Registry::get('log');
            }
            
            if (Zend_Registry::isRegistered('config')) {
                $config = Zend_Registry::get('config');
            }
            
            if (is_string($symbols) && strlen($symbols)) { $symbols = array($symbols); }
            
            if (is_array($symbols) && count($symbols)) {
                
                // Create Two Revolution Cycle
                $need_to_cycle_again = true;
                for ($cycle = 0; $cycle < 2 && $need_to_cycle_again; $cycle++) {
                    $need_to_cycle_again = false;
                    
                    foreach (preg_replace(array('/^\^/', '/(\.|\=).*/'), '', $symbols) as $temp) {
                        $wheres[] = $db->quoteInto('symbol=?', strtoupper($temp));
                    }
                    $query = "SELECT * FROM quote_view_symbol_matrix WHERE " . implode(' OR ', $wheres);
                    
                    #$query = "SELECT * FROM quote_view_symbol_matrix WHERE symbol IN (SELECT DISTINCT UPPER(list_array('{\"" . implode('", "', preg_replace(array('/^\^/', '/(\.|\=).*/'), '', $symbols)) . "\"}'::varchar[16][])))";
                    #$query = "SELECT * FROM quote_view_symbol_matrix WHERE internal_symbol IN (SELECT DISTINCT UPPER(list_array('{\"" . implode('", "', $symbols) . "\"}'::varchar[16][])))";
                    
                    $stmt = $db->query($query);
                    $db_results = $stmt->fetchAll();
                    
                    $data_by_id = $data_internal_symbols = $data_delayed_symbols = $found = $results = array();
                    foreach ($db_results as $line) {
                        $data_by_id[(string) $line->id] = $line;
                        if (!isset($data_internal_symbols[$line->internal_symbol])) {
                            $data_internal_symbols[$line->internal_symbol] = $line->id;
                        }
                        if (!isset($data_delayed_symbols[$line->delayed_symbol])) {
                            $data_delayed_symbols[$line->delayed_symbol] = $line->id;
                        }
                    }
                    
                    for ($i = 0; $i < count($symbols); $i++) {
                        $symbol = strtoupper($symbols[$i]);
                        if (array_key_exists($symbol, $data_internal_symbols) || array_key_exists($symbol, $data_delayed_symbols)) {
                            if (array_key_exists($symbol, $data_delayed_symbols)) {
                                $results[$symbol] = $data_by_id[(string) $data_delayed_symbols[$symbol]];
                            }
                            else {
                                $results[$symbol] = $data_by_id[(string) $data_internal_symbols[$symbol]];
                            }
                            
                            $found[$symbol] = 1;
                        }
                    }

                    if (array_diff($symbols, array_keys($found))) {
                        // Not All Symbols Found
                        foreach (array_diff($symbols, array_keys($found)) as $temp_symbol) {
                            self::lookup($temp_symbol);
                        }
                        
                        $need_to_cycle_again = true;
                    }
                }
            }
        }
        
        return $results;
    }
    
    
    public static function lookupById($ids = null)
    {
        $found = array();
        
        if (Zend_Registry::isRegistered('db')) {
            $db = Zend_Registry::get('db');
            
            if (Zend_Registry::isRegistered('log')) {
                $log = Zend_Registry::get('log');
            }
            
            if (Zend_Registry::isRegistered('config')) {
                $config = Zend_Registry::get('config');
            }
            
            if (is_string($ids) && strlen($ids)) { $ids = array($ids); }
            
            if (is_array($ids) && count($ids)) {
                
                foreach ($ids as $temp) {
                    $wheres[] = $db->quoteInto('id=?', $temp);
                }
                $query = "SELECT * FROM quote_view_symbol_matrix WHERE " . implode(' OR ', $wheres);
                
                # Using IN is SLOOOW - Array Intersect is slow too, but way faster than IN
                #$query = "SELECT * FROM quote_view_symbol_matrix WHERE ARRAY[id] && '{" . implode(', ', $ids) . "}'::bigint[]";
                #$query = "SELECT * FROM quote_view_symbol_matrix WHERE id IN (SELECT DISTINCT list_array('{" . implode(', ', $ids) . "}'::bigint[]))";
                $stmt = $db->query($query);
                $db_results = $stmt->fetchAll();

                foreach ($db_results as $line) {
                    $found[$line->id] = $line;
                }
                    
                #if (array_diff($ids, array_keys($found))) {
                #    // Not All IDs Found
                #    foreach (array_diff($ids, array_keys($found)) as $temp_symbol) {
                #        self::lookup($temp_symbol);
                #    } 
                #}

                
            }
        }
        
        return $found;
    }
}
