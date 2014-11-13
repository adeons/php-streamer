# Streamer

Simple class for PHP 5.3+ to iterate over lazily loaded values.
This is meant to be used in environments where PHP generators are not available (runtime version lower than 5.5).

## Usage

Simple example:

```php
function tenNumbers() {
    $u = 0;

    return new Streamer(function($stream) use (&$u) {
        if ($u < 10) {

            // sets the current value of the Streamer
            $stream->send($u);
            $u ++;
        } else {
            // send is not called here, so the loop will end
        }
    });
}

foreach (tenNumbers() as $number) {
    echo $number;
}
```

To send a value with a key, set the key as the second parameter of `send`:

```php
function numbersAsWords() {
    $u = 0;
    $words = ['zero', 'one', 'two', 'three', 'four'];

    return new Streamer(function($stream) use (&$u, $words) {
        if ($u < 5) {
            // value, key
            $stream->send($words[$u], $u);
            $u ++;
        }
    });
}

foreach (numbersAsWords() as $number => $word) {
    echo "$number is $word";
}
```

### Buffering

Use `sendMany` to iterate over an array or `Traversable` object:

```php
function loadAllPosts() {
    $page = 1;

    return new Streamer(function($stream) use (&$page) {
        $posts = loadPostsPage($page);

        if (empty($posts)) {
            // no more posts were found; end loop
        } else {
            // send loaded posts
            $stream->sendMany($posts);
            $page ++;
        }
    });
}

// iterate over all the loaded pages as if they were one
foreach (loadAllPosts() as $post) {
    echo $post->id;
}
```

### Freeing resources

The constructor second parameter is a closure that will be called when the iterator reaches the end of the loop or is no longer referenced.

```php
function linesFromFile($fileName) {
    $fileHandle = fopen($fileName, 'r');

    return new Streamer(function($stream) use ($fileHandle) {
        $line = fgets($fileHandle);

        if ($line !== false) {
            $stream->send($line);
        }

    }, function() use ($fileHandle) {

        fclose($fileHandle);
    });
}
```
