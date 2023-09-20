<?php

namespace security\wordpress\DynamicTables;

class QueryBuilder {
    private $table;
    private $columns = '*';
    private $orderBy = '';
    private $limit = '';
    private $offset = '';
    private $query = '';

    private $where;
    private $results = array();

    public function __construct( $table ) {
        $this->table = $table;
    }

    public function select( $columns ) {
        $this->columns = $columns;

        return $this;
    }

    public function addSelect( $columns ) {
        $this->columns .= ", $columns";

        return $this;
    }

    public function orderBy( $column, $direction = 'ASC' ) {
        $column        = str_replace( "'", "", $column );
        $this->orderBy = "ORDER BY $column $direction";

        return $this;
    }

    public function limit( $limit, $offset = 0 ) {
        $this->limit  = "LIMIT $limit";
        $this->offset = "OFFSET $offset";

        return $this;
    }

    public function getQuery( $skipLimit = false ) {
        $query = "SELECT $this->columns FROM $this->table";

        //we loop through the $this->>where array and add it to the query
        if ( ! empty( $this->where ) ) {
            $query .= " WHERE ";
            foreach ( $this->where as $where ) {
                $query .= "$where OR ";
            }
            //we remove the last AND
            $query = substr( $query, 0, - 4 );
        }

        if ( ! empty( $this->orderBy ) ) {
            $query .= " $this->orderBy";
        }

        if ( ! $skipLimit ) {
            if ( ! empty( $this->limit ) ) {
                $query .= " $this->limit";
            }

            if ( ! empty( $this->offset ) ) {
                $query .= " $this->offset";
            }
        }

        $this->query = $query;
        //we validate and cleanup the query
        $this->query = str_replace( ';', '', $this->query );
        //we look for a double space and replace it with a single space
        $this->query = str_replace( '  ', ' ', $this->query );
        //we look for a double , and replace it with a single ,
        $this->query = str_replace( ', ,', ',', $this->query );

        return $this->query;
    }

    public function get() {
        $this->results = $this->execute( $this->getQuery() );

        return $this->results;
    }

    public function toSql() {
        $this->getQuery();

        return $this->query;
    }

    public function count() {
        $query = $this->getQuery( true );
        $countQuery = "SELECT COUNT(*) as count FROM ($query) as subquery";

        return $this->execute($countQuery)[0]->count;
    }

    private function execute( $query ) {
        global $wpdb;

        return $wpdb->get_results( $query );
    }

    public function insert( $data ) {
        $columns = array();
        $values  = array();

        foreach ( $data as $column => $value ) {
            $columns[] = $column;
            $values[]  = "'" . esc_sql( $value ) . "'";
        }

        $columns = implode( ', ', $columns );
        $values  = implode( ', ', $values );

        $query = "INSERT INTO $this->table ($columns) VALUES ($values)";

        $this->execute( $query );
    }

    public function update( $data ) {
        $set = array();

        foreach ( $data as $column => $value ) {
            $set[] = "$column = '" . esc_sql( $value ) . "'";
        }

        $set = implode( ', ', $set );

        $query = "UPDATE $this->table SET $set";

        $this->execute( $query );
    }

    public function where( $column, $operator, $value ) {
        //we add it to an array so we can build the query later
        $this->where[] = "$column $operator '" . esc_sql( $value ) . "'";

        return $this;
    }

    public function whereIn( $column, $values ) {
        $column = str_replace( "'", "", $column );
        $values = array_map( 'esc_sql', $values );
        $values = "'" . implode( "', '", $values ) . "'";

        $query = "WHERE $column IN ($values)";

        return $query;
    }

    public function whereNotIn( $column, $values ) {
        $column = str_replace( "'", "", $column );
        $values = array_map( 'esc_sql', $values );
        $values = "'" . implode( "', '", $values ) . "'";

        $query = "WHERE $column NOT IN ($values)";

        return $query;
    }

    public function first() {
        $this->limit( 1 );
        $result = $this->execute( $this->getQuery() );

        return isset( $result[0] ) ? $result[0] : null;
    }

    public function paginate( $rows = 0, $page = 0 ) {
        if ( $page > 0 ) {
            $offset = ( $page - 1 ) * $rows;
        } else {
            $offset = 0;
        }
        $this->limit( $rows, $offset );
        $results = $this->get();
        $total = $this->count();


        $lastPage = ceil( $total / $rows );

        return [
            'data'       => $results,
            'pagination' => [
                'totalRows'   => $total,
                'perPage'     => $rows,
                'offset'      => $offset,
                'currentPage' => $page,
                'lastPage'    => $lastPage,
            ],
            //if the debug option in WordPress is set to true, the query will be returned
             'query' => $this->toSql(), //- uncomment this line if you want to see the query
        ];
    }

    /**
     * Get all columns from the table
     *
     * @return array
     */
    public function getColumns(): array {
        //we return all columns from the table
        $query  = "SHOW COLUMNS FROM $this->table";
        $result = $this->execute( $query );

        return array_column($result, 'Field');
    }
}