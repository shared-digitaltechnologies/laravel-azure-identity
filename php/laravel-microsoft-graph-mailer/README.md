# `shrd/laravel-microsoft-graph-mailer`

This package was generated by the `@shrd/nx-php` plugin.

## Configuration

Merge your `config/mail.php` with the following:

```{php}
return [
    "mailers" => [
        "microsoft-graph" => [
            "transport" => "microsoft-graph",
            "credential_driver" => env('MAIL_MICROSOFT_GRAPH_CREDENTIAL_DRIVER'), // Defaults to the default azure credential of the app.
            "save_to_sent_items" => env('MAIL_MICROSOFT_GRAPH_SAVE_TO_SENT_ITEMS', false) // Save the emails in the sent items of the mailbox?
        ]
    ]
]
```

## Commands

To make an archive-file of this package that can be imported by other php applications, run:

```{shell}
nx run laravel-microsoft-graph-mailer:build
```

To test this package, run:

```{shell}
nx run laravel-microsoft-graph-mailer:test
```