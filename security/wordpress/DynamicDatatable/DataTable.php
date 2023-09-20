<?php

namespace security\wordpress\DynamicTables;

class DataTable {

    /**
     * @var mixed
     */
    public $post;
    /**
     * @var array|int[]
     */
    private $paging;
    private $queryBuilder;

    /**
     * @var array
     */
    private $validateRaw;

    public function __construct( $POST, QueryBuilder $queryBuilder ) {
        $this->post         = $POST;
        $this->queryBuilder = $queryBuilder;
    }


    /**
     * This class validates all sorting parameters
     * @throws Exception
     */
    public function validateSorting() {
        //first we check if the sortColumn and sortDirection are set
        if ( isset( $this->post['sortColumn'] ) && isset( $this->post['sortDirection'] ) ) {
            //then we check if the sortColumn is a valid column
            if (
                ! in_array( $this->post['sortColumn']['column'], $this->queryBuilder->getColumns() )
            ) {
                //we also check if it is in the validateRaw array
                if ( ! in_array( $this->post['sortColumn']['column'], $this->validateRaw ) ) {
                    throw new Exception( 'Invalid sort column' );
                }
            }
            //then we check if the sortDirection is a valid direction
            if ( ! in_array( $this->post['sortDirection'], array( 'asc', 'desc' ) ) ) {
                throw new Exception( 'Invalid sort direction' );
            }
            $this->queryBuilder->orderBy( $this->post['sortColumn']['column'], $this->post['sortDirection'] );
        }

        return $this;
    }

    private function getColumns() {
        return $this->queryBuilder->getColumns();
    }

    /**
     * @throws Exception
     */
    public function setSelect( array $array ) {
        //we loop through the array and check if the column is valid
        // and if the column starts with raw: we exclude it from the check
        $rawColumns = [];
        foreach ( $array as $column ) {
            if ( strpos( $column, 'raw:' ) === false ) {
                if ( ! in_array( $column, $this->getColumns() ) ) {
                    throw new Exception( 'Invalid column' );
                }
            } else {
                //we remove the column from the array and add it to the rawColumns array
                unset( $array[ array_search( $column, $array ) ] );
                $rawColumns[] = str_replace( 'raw:', '', $column );
            }
        }
        //we get the first array element and add it to the query
        $this->queryBuilder->select( $array[0] );
        //we loop through the rest of the array and add it to the query
        for ( $i = 1; $i < count( $array ); $i ++ ) {
            $this->queryBuilder->addSelect( $array[ $i ] );
        }
        //we add the raw columns to the query
        foreach ( $rawColumns as $rawColumn ) {
            $this->queryBuilder->addSelect( $rawColumn );
            //we extract the column name from the raw column
            $columnName = explode( ' as ', $rawColumn )[1];
            //we add the column name to the columns array
            $this->validateRaw[] = $columnName;
        }

        return $this;
    }

    public function getResults() {
        return $this->queryBuilder->paginate( ...$this->paging );
    }

    /**
     * @throws Exception
     */
    public function validatePagination() {
        $perPage = 10;
        $page    = 1;
        //we check if the paging parameters are set
        if ( isset( $this->post['page'] ) ) {
            //we check if the page is a number
            if ( ! is_numeric( $this->post['page'] ) ) {
                throw new Exception( 'Invalid page number' );
            }
            $page = $this->post['page'];
        }

        if ( isset( $this->post['currentRowsPerPage'] ) ) {
            //we check if the perPage is a number
            if ( ! is_numeric( $this->post['currentRowsPerPage'] ) ) {
                throw new Exception( 'Invalid per page number' );
            }
            $perPage = $this->post['currentRowsPerPage'];
        }
        $this->paging = [ $perPage, $page ];

        return $this;
    }

    public function validateSearch() {
        if ( isset( $this->post['search'] ) && count( $this->post['searchColumns'] ) > 0 ) {

            //we check if the searchColumns are valid
            foreach ( $this->post['searchColumns'] as $column ) {
                if ( ! in_array( $column, $this->getColumns() ) ) {
                    throw new Exception( 'Invalid search column' );
                }
            }
            //we add the search to the query
            foreach ( $this->post['searchColumns'] as $column ) {
                $this->queryBuilder->where( $column, 'like', '%' . $this->post['search'] . '%' );
            }
        }

        return $this;
    }
}