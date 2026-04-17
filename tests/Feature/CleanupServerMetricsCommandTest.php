<?php

test('example', function () {
    $this->get('/')->assertRedirect();
});
