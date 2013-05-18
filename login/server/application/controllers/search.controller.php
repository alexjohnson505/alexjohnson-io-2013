<?php

class SearchController extends Controller{

    function get($query){
        $search = new GlobalSearch();

        Response($search->do_search($query));
    }
}