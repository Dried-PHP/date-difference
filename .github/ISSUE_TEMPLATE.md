<!--
    🛑 Important notice to read.

    Before anything else, please search in previous issues (both open and close):
    https://github.com/dried/date-utils/issues?q=is%3Aissue

    ⚠️ Please don't create an issue about a problem already discussed or addressed.

    ⚠️ Don't remove this template.
    This issue template applies to all
      - bug reports,
      - feature proposals,
      - and documentation requests

    Having all those information will allow us to know exactly
    what you expect and answer you faster and precisely (answer
    that matches your Carbon version, PHP version and usage).
    
    Note: Comments between <!- - and - -> won't appear in the final
    issue (See [Preview] tab).
-->
Hello,

I encountered an issue with the following code:
<!--
    ⚠️ Your code below (not the code from inside date-utils library)
    Be sure it's dependency-free and enough to reproduce the issue
    when we run it on:
-->
```php
echo new Rounding()->roundBy(26, by: 6);
```

Carbon version: **PUT HERE YOUR dried/date-utils VERSION (exact version, not the range)**
<!--
    ⚠️ Run the command `composer show dried/date-utils` to get "versions"
-->

PHP version: **PUT HERE YOUR PHP VERSION**

<!--
    Use `echo phpversion();`
    to get PHP version.
-->


I expected to get:

```
24
```
<!--
    Always give your expectations. Each use has their owns.
    You may want daylight saving time to be taken into account,
    someone else want it to be ignored. You may want timezone
    to be used in comparisons, someone else may not, etc.
-->

But I actually get:

```
26
```

Thanks!
