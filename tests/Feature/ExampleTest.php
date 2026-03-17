<?php

it('renders the welcome page', function () {
    $this->get('/')->assertSuccessful();
});
