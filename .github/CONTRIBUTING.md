# Contributing to DecAPI
Thanks for showing interest in contributing to DecAPI. While there are [a handful of contributors](https://github.com/Decicus/DecAPI/graphs/contributors), it is primarily by developed by one person right now and contributions are generally welcome.

Pull requests are welcome for new features, but ideally I'd like to discuss it via [Discord][Discord] or through [issues][GH-Issues] before anything is implemented.

This document (`CONTRIBUTING`) is currently work in progress and may be missing some information.  
Feel free to contact me via [Discord][Discord], [GitHub issues][GH-Issues] or [my personal email](mailto:alex@thomassen.xyz) if you have any questions.

## Translations
The easiest way to contribute is by providing translations. You can see the currently available translations in the [`resources/lang`][Dir-Lang] directory of the project.  
[ISO 3166-1 alpha-2 codes][Lang-Codes] are used for referring to languages.

DecAPI supports some basic translations for certain endpoints, but it's not implemented fully everywhere.  
Most translations are provided by users of DecAPI.  
There's some information about [translations on the documentation website][Docs-Localization].

## Coding conventions
The project repository has a [`.editorconfig`][File-EditorConfig] file that defines a few basic rules for how files should be formatted.  
There are [EditorConfig plugins for various editors][EditorConfig-DL] that you can download to make your editor automatically format the files based on the `.editorconfig` file.

The following basic rules should apply and are already defined in the `.editorconfig` file:

- Indentation: 4 spaces - Sometimes referred to as "soft tabs"
- Newlines: Unix-style - Sometimes referred to as `LF` or `\n`
- Empty lines are fine, but they should not include any whitespace
- Lines should not have any trailing whitespace
    - The exception is Markdown files, where trailing whitespace might indicate a line break

These formatting rules apply even if the file is not following is already, as there's a chance some of the files from early development have not been edited since `.editorconfig` was added.

[Dir-Lang]: /resources/lang
[Discord]: https://links.decapi.me/discord
[Docs-Localization]: https://docs.decapi.me/#localization
[EditorConfig]: https://editorconfig.org/
[EditorConfig-DL]: https://editorconfig.org/#download
[File-EditorConfig]: /.editorconfig
[GH-Issues]: https://github.com/Decicus/DecAPI/issues
[Lang-Codes]: https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements
