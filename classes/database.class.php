<?php
/**
TS Comfort Database - A Database Explorer for Wordpress
Copyright (C) 2017 Tobias Spiess

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
* Database Utility Class that uses Wordpress Database Functions
* @author Tobias Spiess
*/
if(!class_exists('TS_INF_COMFORT_DB_DATABASE')) {
	class TS_INF_COMFORT_DB_DATABASE
	{
		/**
		 * Get Tablenames and Meta-Information
		 * @return array Object-Array with Tablenames and Table Meta-Information
		 */
		public static function get_tables()
		{
			$tables = array();

			global $wpdb;
			$tables = $wpdb->get_results( "
					SELECT TABLE_NAME, TABLE_TYPE, TABLE_ROWS, TABLE_COMMENT 
					FROM information_schema.tables 
					WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE='BASE TABLE' 
					OR TABLE_TYPE='VIEW'
					ORDER BY TABLE_NAME;" );

			return $tables;
		}
		
		/**
		 * Get Tablenames
		 * @return array Object-Array with Tablenames
		 */
		public static function get_table_names()
		{
		    $tables = array();
		    
		    global $wpdb;
		    $result = $wpdb->get_results( "
					SELECT TABLE_NAME
					FROM information_schema.tables
					WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE='BASE TABLE'
					OR TABLE_TYPE='VIEW'
					ORDER BY TABLE_NAME;");
		    
		    
		    if(is_array($result) && count($result) > 0)
		    {
		        foreach($result as $table)
		        {
		            $tables[] = $table->TABLE_NAME;
		        }
		    }
		    
		    return $tables;
		}
		
		/**
		 * Get Table Content
		 * @param  string $tablename        Table Name
		 * @param  integer [$limit = 50]     Number of Rows
		 * @param  integer [$page = 1]       Pagenumber
		 * @param  string [$orderby = null] Column-Name
		 * @param  string [$order = null]   asc or desc
		 * @param string WHERE String without 'WHERE'
		 * @return array Object-Array with Table Data
		 */
		public static function get_table_data($tablename, $limit = 50, $page = 1, $orderby = null, $order = null, $where = '')
		{
			$offset = ($limit * $page) - $limit;
						
			$table_data = array();
			if(self::table_exists($tablename))
			{
				$order_string = '';
				if(!is_null($orderby) && !is_null($order) && ($order === 'asc' || $order === 'desc'))
				{
					$order_string = " ORDER BY " . $orderby . " " . strtoupper($order);
				}
				
				$where = trim($where);
				$where_string = '';
				if(strlen($where) > 0)
				{
					$where_string = " WHERE " . $where;
				}
				
				global $wpdb;
				$sql = $wpdb->prepare( "
						SELECT *
						FROM " . $tablename .
				    " " . $where_string . " " .
				    $order_string . "
						LIMIT %d,%d",
				    $offset,$limit
			    );
				
				$table_data = $wpdb->get_results($sql);
			}
			
			return $table_data;
		}
		
		/**
		 * Checks existence of a Table
		 * @param  string $tablename Table Name
		 * @return boolean true if table exists otherwise false
		 */
		public static function table_exists($tablename)
		{
			global $wpdb;
			$table_check = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(1) as test 
            FROM information_schema.tables 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = %s
            LIMIT 1", $tablename));
            
			if($table_check === 1)
			{
				return true;
			} else {
				return false;
			}
		}
		
		/**
		 * Checks wether a column is a primary key
		 * @param  string $table_name   Table Name
		 * @param  string $table_column Table Column
		 * @return boolean  true if column is a primary key otherwise false
		 */
		public static function is_primary_key($table_name, $table_column)
		{
			global $wpdb;
			$pk_chk = $wpdb->get_var( $wpdb->prepare(
					"SELECT k.COLUMN_NAME
					FROM information_schema.table_constraints t
					LEFT JOIN information_schema.key_column_usage k
					USING(constraint_name,table_schema,table_name)
					WHERE t.constraint_type='PRIMARY KEY'
					AND t.table_schema=DATABASE()
					AND t.table_name=%s 
					AND k.COLUMN_NAME LIKE %s;",
					$table_name,
					$table_column
			) );
			
			if(is_null($pk_chk))
			{
				return false;
			} else {
				return true;
			}
		}
		
		/**
		 * Checks if a column can be null
		 * @param  string $table_name   Table Name
		 * @param  string $table_column Table Column
		 * @return boolean  true if column can be null otherwise false
		 */
		public static function can_be_null($table_name, $table_column)
		{
		    global $wpdb;
		    $sql = $wpdb->prepare(
		        "SELECT c.IS_NULLABLE
					FROM information_schema.COLUMNS c
					WHERE c.TABLE_SCHEMA=DATABASE()
					AND c.TABLE_NAME=%s
					AND c.COLUMN_NAME LIKE %s;",
		        $table_name,
		        $table_column
	        );
		    
		    $can_be_null = $wpdb->get_var($sql);
		    
		    if($can_be_null === 'YES')
		    {
		        return true;
		    } else {
		        return false;
		    }
		}
		
		/**
		 * Checks wether a column is a foreign key
		 * @param  string $table_name   Table Name
		 * @param  string $table_column Table Column
		 * @return boolean  true if column is a foreign key otherwise false
		 */
		public static function is_foreign_key($table_name, $table_column)
		{
			global $wpdb;
			$fk_chk = $wpdb->get_var( $wpdb->prepare(
					"SELECT k.COLUMN_NAME
					FROM information_schema.table_constraints t
					LEFT JOIN information_schema.key_column_usage k
					USING(constraint_name,table_schema,table_name)
					WHERE t.constraint_type='FOREIGN KEY'
					AND t.table_schema=DATABASE()
					AND t.table_name=%s
					AND k.COLUMN_NAME LIKE %s;",
					$table_name,
					$table_column
					) );
			
			if(is_null($fk_chk))
			{
				return false;
			} else {
				return true;
			}
		}
		
		/**
		 * Returns primary key columns of a table
		 * @param  string $table_name Table Name
		 * @return array Column Names of primary key columns
		 */
		public static function get_primary_key_columns($table_name)
		{
			$pk_columns = array();
			
			global $wpdb;
			$pk_columns_result = $wpdb->get_results( $wpdb->prepare(
					"SELECT k.COLUMN_NAME
					FROM information_schema.table_constraints t
					LEFT JOIN information_schema.key_column_usage k
					USING(constraint_name,table_schema,table_name)
					WHERE t.constraint_type='PRIMARY KEY'
					AND t.table_schema=DATABASE()
					AND t.table_name=%s;",
					$table_name
					) );
			
			if(is_array($pk_columns_result) && count($pk_columns_result) > 0)
			{
				foreach($pk_columns_result as $column)
				{
					$pk_columns[] = $column->COLUMN_NAME;
				}
			}
			
			return $pk_columns;
		}
		
		/**
		 * Returns Data Type of a Table Column
		 * @param  string $table_name   Table Name
		 * @param  string $table_column Table Column
		 * @return string Data Type Name
		 */
		public static function get_column_type($table_name, $table_column)
		{
			global $wpdb;
			$type = $wpdb->get_var( $wpdb->prepare(
					"SELECT DATA_TYPE 
					FROM INFORMATION_SCHEMA.COLUMNS 
					WHERE table_name = %s 
					AND COLUMN_NAME = %s;",
					$table_name,
					$table_column
					) );
			
			return $type;
		}
		
		/**
		 * Returns Column Names of a Table
		 * @param  string $table_name Table Name
		 * @return array Column Names
		 */
		public static function get_column_names($table_name)
		{
			global $wpdb;
			$names_res = $wpdb->get_results( $wpdb->prepare(
					"SELECT COLUMN_NAME 
					FROM INFORMATION_SCHEMA.COLUMNS 
					WHERE TABLE_SCHEMA = DATABASE() 
					AND table_name = %s;",
					$table_name
					) );
			
			$names = array();
			if(is_array($names_res) && count($names_res) > 0)
			{
				foreach($names_res as $item)
				{
					$names[] = $item->COLUMN_NAME;
				}
			}
			
			return $names;
		}
		
		/**
		 * Returns Relation Information of a foreign key column
		 * @param  string $table_name   Table Name
		 * @param  string $table_column Foreign Key Table Column
		 * @return array Relation Information of a foreign key column
		 */
		public static function get_foreign_key_column_relations($table_name, $table_column)
		{
			global $wpdb;
					
			$relations = $wpdb->get_results( $wpdb->prepare(
					"SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
					FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
					WHERE TABLE_NAME = %s
					AND COLUMN_NAME = %s 
					AND REFERENCED_TABLE_NAME IS NOT NULL
					AND REFERENCED_COLUMN_NAME IS NOT NULL
					GROUP BY REFERENCED_COLUMN_NAME;",
					$table_name,
					$table_column
					) );
			
			return $relations;
		}
		
		/**
		 * Checks wether a table column is auto increment
		 * @param  string $table_name  Table Name
		 * @param  string $column_name Column Name
		 * @return boolean  true if column is auto increment otherwise false
		 */
		public static function is_auto_increment($table_name, $column_name)
		{
			global $wpdb;
			$auto_inc_chk = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(1) AS rowcount
					FROM INFORMATION_SCHEMA.COLUMNS
					WHERE TABLE_SCHEMA = DATABASE() 
					AND TABLE_NAME = %s 
					AND COLUMN_NAME = %s
					AND extra LIKE '%%auto_increment%%'",
					$table_name,
					$column_name
			) );
			
		   if(is_null($auto_inc_chk) || $auto_inc_chk < 1)
		   {
				return false;
		   } else {
				return true;
		   }
		}
		
		/**
		 * Returns Meta Data of a Column (Data Type, Primary Key Check, Foreign Key Check, Auto Increment Check, Relations)
		 * @param  string $table_name  Table Name
		 * @param  string $column_name Column Name
		 * @return object Meta Information in an object of Type TS_INF_COMFORT_DB_COLUMN_META
		 */
		public static function get_column_metadata($table_name, $column_name)
		{
			$meta_data = new TS_INF_COMFORT_DB_COLUMN_META();
			
			$meta_data->is_primary_key = self::is_primary_key($table_name, $column_name);
			$meta_data->is_foreign_key = self::is_foreign_key($table_name, $column_name);
			$meta_data->type = self::get_column_type($table_name, $column_name);
			$meta_data->auto_inc = self::is_auto_increment($table_name, $column_name);
			$meta_data->can_be_null = self::can_be_null($table_name, $column_name);
			
			if($meta_data->is_foreign_key === true)
			{
				$meta_data->foreign_key_relations = self::get_foreign_key_column_relations($table_name, $column_name);
			}
			
			return $meta_data;
		}
		
		/**
		 * Does a Fulltext Search on Database
		 * @param  string $searchterm Search Term
		 * @return array Result-Set with Tablenames an the number of matches in the table
		 */
		public static function do_full_text_search($searchterm)
		{
			$search_result = array();
			
			$searchterm = htmlentities(strip_tags($searchterm), ENT_QUOTES);
			
			global $wpdb;
			
			$tables = self::get_tables();
			if(is_array($tables) && count($tables) > 0)
			{   
				foreach($tables as $table)
				{
					$tablename = $table->TABLE_NAME;
					$column_names = self::get_column_names($tablename);
					if(is_array($column_names) && count($column_names) > 0)
					{
						$column_count = count($column_names);
						$column_counter = 0;
						
						$sql = "SELECT COUNT(1) AS result_count FROM " . $tablename . " WHERE ";
						$where = "";
						foreach($column_names as $column_name)
						{
							$column_counter++;
							if($column_count === $column_counter)
							{
								$where .= "`" . $column_name . "` LIKE '%" . $searchterm . "%';";
							} else {
								$where .= "`" . $column_name . "` LIKE '%" . $searchterm . "%' OR ";
							}
						}
						$sql .= $where;
						
						$result_count = (int) $wpdb->get_var($sql);
						
						if($result_count > 0)
						{
							$search_result[$tablename] = $result_count;
						}
					}
				}
				
				return $search_result;
			}
		}
		
		/**
		 * Does a Fulltext Search on a Table
		 * @param  string $searchterm Search Term
		 * @param  string $tablename  Table Name
		 * @return array Result-Set with results of Fulltext Search
		 */
		public static function do_full_text_search_in_table($searchterm, $tablename)
		{
			$result = array();
			
			$column_names = self::get_column_names($tablename);
			if(is_array($column_names) && count($column_names) > 0)
			{
				global $wpdb;
				
				$column_count = count($column_names);
				$column_counter = 0;

				$sql = "SELECT * FROM " . $tablename . " WHERE ";
				$where = "";
				foreach($column_names as $column_name)
				{
					$column_counter++;
					if($column_count === $column_counter)
					{
						$where .= "`" . $column_name . "` LIKE '%" . $searchterm . "%';";
					} else {
						$where .= "`" . $column_name . "` LIKE '%" . $searchterm . "%' OR ";
					}
				}
				$sql .= $where;

				$result = $wpdb->get_results($sql);
			}
			
			return $result;
		}
		
		/**
		 * Does Field Search on a table
		 * @param  array $values Search-Values - Key: Column Name - Value: Search Term for the Column
		 * @param  string $tablename Table Name
		 * @return array Result-Set with results of Table Search
		 */
		public static function do_field_search_in_table($values, $tablename)
		{
			$result = array();
			
			if(is_array($values) && count($values) > 0)
			{
				global $wpdb;
				
				$column_names = array_keys($values);
				
				$column_count = count($column_names);
				$column_counter = 0;

				$sql = "SELECT * FROM " . $tablename . " WHERE ";
				$where = "";
				foreach($values as $column_name => $searchterm)
				{
					$column_counter++;
					if($column_count === $column_counter)
					{
						$where .= "`" . $column_name . "` LIKE '%" . $searchterm . "%';";
					} else {
						$where .= "`" . $column_name . "` LIKE '%" . $searchterm . "%' AND ";
					}
				}
				$sql .= $where;

				$result = $wpdb->get_results($sql);
			}
			
			return $result;
		}
		
		/**
		 * Get count of rows in a table
		 * @param  string $tablename Table Name
		 * @param string WHERE String without 'WHERE'
		 * @return integer row-count
		 */
		public static function get_total_table_count($tablename, $where = '')
		{
			global $wpdb;
			
			$sql = "SELECT COUNT(1) AS result_count FROM " . $tablename . ";";
			
			$where = trim($where);
			$where_string = '';
			if(strlen($where) > 0)
			{
				$where_string = " WHERE " . $where;
				$sql = "SELECT COUNT(1) AS result_count FROM " . $tablename . $where_string . ";";
			}
					
			$result_count = $wpdb->get_var($sql);
			return (int) $result_count;
		}
		
		/**
		 * Get size of the database
		 * @return float
		 */
		public static function get_used_space_database()
		{
		    global $wpdb;
		    
		    $sql = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS database_size
                    FROM information_schema.TABLES
                    WHERE table_schema = DATABASE()
                    GROUP BY table_schema;";
		    
		    $result_size = $wpdb->get_var($sql);
		    
		    return $result_size;
		}
		
		/**
		 * Get size of a table
		 * @param string $tablename
		 * @return float
		 */
		public static function get_used_space_table($tablename)
		{
		    global $wpdb;
		    
		    $sql = $wpdb->prepare("SELECT round(((data_length + index_length) / 1024 / 1024), 2) as table_size
                    FROM information_schema.TABLES 
                    WHERE table_schema = DATABASE() AND table_name = %s;", $tablename);
		    
		    $result_size = $wpdb->get_var($sql);
		    
		    return $result_size;
		}
		
		/**
		 * Get MySQL Version
		 * @return string
		 */
		public static function get_mysql_version()
		{
		    global $wpdb;
		    $mysql_version = $wpdb->get_var("SELECT VERSION() as mysql_version");
		    return $mysql_version;
		}
		
		/**
		 * Get Engine of a table
		 * @param string $tablename
		 * @return string
		 */
		public static function get_table_engine($tablename)
		{
		    global $wpdb;
		    $sql = $wpdb->prepare("SELECT ENGINE 
                                    FROM information_schema.TABLES
		                            WHERE TABLE_SCHEMA = DATABASE() 
                                    AND TABLE_NAME = %s;", $tablename);
		    
		    $result_engine = $wpdb->get_var($sql);
		    
		    return $result_engine;
		}
	}
}