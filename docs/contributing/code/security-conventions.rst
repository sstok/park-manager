Security Conventions
====================

Security is the most important quality criteria of a hosting platform.

In short: **When one website gets hacked it's a problem.
When the hosting platform gets hacked, it's everyone's problem!**

The goal of this document is to prevent known problems, and to enforce
proper practices. *This is not a single solution to every possible problem.*

Park-Manager's infrastructure is powered by the Symfony framework
which already takes care of a number of concerns including CSRF tokens,
user Authentication and authorization, logging, and input sanitation.
And are therefor not listed in detail here.

.. note::

    Security is a moving target. New exploits and attack vectors are
    discovered almost everyday. Be sure to follow keep yourself up-to-date
    with the latest developments.

    Be sure to follow the excellent `Paragon Initiative Enterprises Blog <https://paragonie.com/blog>`_
    for new developments and security best practices.

Understanding the Essence of Security (introduction)
----------------------------------------------------

Please set aside most of what you've heard over the years; chances are,
most of it just muddies the water. **Security is not a product.**
**Security is not a checklist.** Security is not an absolute.

Security is a process. Security is an emergent property of a mature mindset
in the face of risk.

**Perfect security is not possible**, but attackers do have budgets.
If you raise the cost of attacking a system (your application or the networking
infrastructure it depends on) so high that the entities that would be interested
in defeating your security are incredibly unlikely to succeed, you'll be incredibly
unlikely to be compromised.

Even better, the most effective ways to raise the cost of attack against your
system don't significantly increase the cost of using the system legitimately.
This is no accident; as Avi Douglen says, "Security at the expense of usability,
comes at the expense of security."

If your goal is to write secure (PHP) applications:

* You generally don't need to know who the attacker is.
* You generally don't need to know why they want to break in.

Conversely:

* You should know what attacks are possible.
* You should know where attacks should come from.
* If an incident does occur, you should know when and how it happened,
  so you can prevent it (and similar attacks) from happening again.

Your system might not be the end-game, especially if the attacker is sophisticated.
Always look to improve. Security is a process. It's not a destination.

Source: `Securing a PHP Application: The Pocket Guide <https://paragonie.com/b/NhIrqgLVV4erjlUE>`_

Reporting security issues
-------------------------

Please don't report security issues publicly, but follow the procedure
described in :ref:`reporting-a-security-issue`.

Information Model
-----------------

Park-Manager does not only contain user information, but also any information
the users store (including emails and personal information about *their* users).

To prevent disclosure or corruption any access requires proper authorization.

* Authentication information is stored in a database (SQL, LDAP, NoSQL).
    * Access to the database is restricted to the application and system administrators.
    * To prevent access from the outside a Firewall must be implemented by the sysadmin.
    * Passwords are stored in accordance with the [password storage requirements].

* Sensitive data (home address, TLS private keys, etc) is stored encrypted.
    * Encryption is performed with either symmetric (single key) or asymmetric cryptography (public/key pair).
    * For searching encrypted data see: `Indexing encrypted data`_.
    * Storage of TLS private keys for hosting is done using asymmetric cryptography,
      only the ``configuration-update`` daemon can decrypt the private keys.

* TLS private keys (decrypted for usage) are stored under the root system-user,
  with strict system ACL/chmod applied (only the root system-user can read them).

* Access to email storage is restricted to the email platform (Postfix/Exim, Dovecot)
  and must not be readable by hosting users or from the application platform directly.

  Database access from the email platform is read-only trough custom DB functions,
  the functions validate access and update the audit logs.

  *Functions are executed with ``SECURITY DEFINER`` - access right of the function owner.*

.. _`Indexing encrypted data`: https://paragonie.com/white-paper/2015-secure-php-data-encryption#index-encrypted-information

Principles
----------

.. note::

    First of **Make sure you understand why something is (in)secure.**
    Don't blindly implement something because an expert told you too.

**Requirements:**

* Prevent data from corrupting the instructions that operate on it.
    * Validate and sanitize user input;
    * Guard against information injection (SQL, XPath, Regex injections, etc);
    * Disallow NUL bytes in non-binary data;

* Be explicit and comprehensive with the application logic;

* Assume that all code that is not tested is vulnerable to attacks;

* Keep dependencies and software up to date and don't rely on abandoned components;

* Don't write your own cryptography;

* Disable content sniffing (for Internet Explorer), explicitly provide a ``Content-Type``;

* Set ``X-Download-Options: noopen`` (for Internet Explorer) to force a download,
  rather then executing it within the providing website's context;

* Employ the `principle of least privilege`_;

* Enable TLS (whenever possible) with strong ciphers, see also :ref:`TLS/SSL <tls-ssl>` requirements;

* Use only (revokable) authorization tokens for third-party API communication.
  No shared username/passwords allowed.

  *Contact the service provider when is this not (yet) supported.*

For a Symfony (powered) application use the `NelmioSecurityBundle`_.

See also: `security-guide-for-developers`_ for a complete (and up-to-date) list of recommendations.

.. _`NelmioSecurityBundle`: https://github.com/nelmio/NelmioSecurityBundle
.. _`principle of least privilege`: https://en.wikipedia.org/wiki/Principle_of_least_privilege
.. _`security-guide-for-developers`: https://github.com/FallibleInc/security-guide-for-developers

Secure defaults
~~~~~~~~~~~~~~~

Set all configuration to be secure by default, **use strong encryption ciphers
and disable weak encryption protocols**. Require strong passwords by default.

Allow the implementor to weaken security, but provide proper warnings
with what will happen if they lower the security settings.

Sanitize and validate user-input
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Input provided by either a web form, REST API, client-side cookie, file upload processing,
HTML5 storage, or response processing from an outgoing API request.

Anything that is provided/communicated by someone or something not part of the trusted
information system (database, system cache, local filesystem).

* Don't trust any received information blindly, validate **and** sanitize.

* Enforce strict rules about which values and formats are allowed;
    * Either "a username must not contain special characters like ``!#%&+<>?``";
    * Restrict long values, unless this is required;
    * Reject unsupported content-types;
    * Validate the received data conform the expected content format;
    * Do strict type checking, check something is a string rather than that something is an array;
    * Use a safe-list, not a forbidden-list for characters, formats and accepted values.

* Guard against known attack like `XML entity expansion`_, `XML Injection`_
  or JSON hash table collision.

* Don't process user-input values trough PHP's ``unserialize`` function!
  Use save serialization with XML, CVS or JSON.

* Disallow deep nesting of data structures, restrict depth.
    * Use ``XMLReader`` to prevent reading to much data in memory.
    * Use the ``$depth`` parameter for `json_decode <http://php.net/manual/en/function.json-decode.php>`_

.. _`XML entity expansion`: https://www.owasp.org/index.php/XML_External_Entity_(XXE)_Processing
.. _`XML Injection`: http://phpsecurity.readthedocs.io/en/latest/Injection-Attacks.html#xml-injection

Remove sensitive memory data after usage
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Normally this is not needed, but to prevent leaking sensitive memory data
in a core-dump or process exploit it is a good practice to "remove" (zero out)
sensitive data from memory once it's no longer used::

    if (!\password_verify($password, $record['password'])) {
        // The $password is invalid, the but $record['password'] string
        // should be removed from memory.
        sodium_memzero($record['password']);

        throw new AuthenticationError('Invalid username or password.');
    }

    // The $password is valid, so remove both from memory.
    sodium_memzero($password);
    sodium_memzero($record['password']);

This includes but is not limited to: (plain) password strings, token strings,
encryption keys, credit-card/social-security numbers. **Any data that when leaked
will have a big impact on a users security.**

But keep the following in mind:

* Use only ``sodium_memzero`` or a low-level C implementation;
  Don't simple ``unset`` or set ``\0`` on the value, this is not enough!

* Only remove data that is actually sensitive, as this operation
  will overwrite the memory segment with ``NUL`` data for the length.

* This method does not actually release the memory space, use ``unset`` for that.

* Don't remove invalid information, a token that doesn't produce a valid result
  is properly bogus input and can be ignored. Else you may risk a DoS attack
  for large strings.

Output/usage escaping
.....................

Not properly escaping dynamic output (like user input) can lead to various
security issues. Where `Cross-site scripting (XSS)`_ is the most common.

The only proper way to prevent this is context-aware escaping.
*Escape a string for usage in HTML (using ``htmlspecialchars()``).*

But instead of manually escaping all dynamic output, it's better to use
a template system that already does this for you.

`Twig <http://twig.sensiolabs.org/>`_ provides a powerful auto escaping system,
that solve 99% of the escaping problems we all encounter, it's therefor the
default template-engine of Symfony and Park-Manager.

.. caution::

    HTML attributes may not always be properly escaped, be sure to use
    the `escape filter <http://twig.sensiolabs.org/doc/filters/escape.html>`_
    with the proper context-type ``html_attr`` for attributes.

Always test if you are not sure escaping is done properly.
**Never disable auto-escaping application globally!**

For content systems it's advised to use special mark-up languages, like Markdown or UBB.

If user-provided HTML must be supported, run it trough `HTML Purifier`_ (before
putting it in storage).

.. _`Cross-site scripting (XSS)`: https://www.owasp.org/index.php/Cross-site_Scripting_(XSS)
.. _`HTML Purifier`: http://htmlpurifier.org/

Command line (shell execution)
------------------------------

First of, try to limit the communication with the Command line (or shell execution).
The hosting platform should be as environment agnostic as possible.

* Never use the back-tick operator `` ` `` for executing commands.

* Never directly use the PHP command-line functions (popen, proc_open, exec, etc),
  use the Symfony Process component to safely execute command-line operations.

* Use ``Process`` with an array to safely compose a command-line operation.

* Only use ``Process`` (with a string) for commands that never change
  or require special operations like ``> some-data``.

  Make sure to properly escape any command and arguments used in ``Process``.
  Use ``Symfony\Component\Process\ProcessUtils::escapeArgument()`` instead of ``escapeshellarg``;

.. note::

    Don't directly communicate with the operating system (OS) to create a new
    system user, update storage quota, or perform any root-user operation.

    Use the :ref:`System ServiceBus <system-service-bus>` instead.

See also: `Command Injection - OWASP <https://www.owasp.org/index.php/Command_Injection>`_

Cryptography (storage)
----------------------

Cryptography is a really *really* complex subject, there a number of things
you must take care of (forget one, and the whole Cryptography system is broken).

* **Confidentiality:** The ability to prevent eavesdroppers from discovering
  the plaintext message, or information about the plaintext message (either `hamming weight`_).

* **Integrity:** The ability to prevent an active attacker from modifying the
  message without the legitimate users noticing.

  This is usually provided via a Message Integrity Code (MIC).

* **Authenticity:** The ability to prove that a message was generated by a
  particular party, and prevent forgery of new messages.

  This is usually provided via a Message Authentication Code (MAC).
  Note that authenticity automatically implies integrity.

**Don't write your own cryptography.**

.. tip::

    PHP (since version 7.2) comes with pre-bundled support for Libsodium,
    a powerful and easy to use crypto library for developers.

    Halite provided by Paragon Initiative Enterprises is the preferred way
    of using Libsodium. It provides a number of extra's to strengthen
    Libsodium's already powerful crypto engine.

    But using Libsodium directly is also allowed.

.. caution::

    Don't use a password as encryption key, use the library's provided
    key derivation functions (to protect against password cracking).

    .. code-block:: php

        use ParagonIE\HiddenString\HiddenString;
        use ParagonIE\Halite\KeyFactory;

        $passwd = new HiddenString('correct horse battery staple');
        // Use random_bytes(16); to generate the salt:
        $salt = "\xdd\x7b\x1e\x38\x75\x9f\x72\x86\x0a\xe9\xc8\x58\xf6\x16\x0d\x3b";

        $encryptionKey = KeyFactory::deriveEncryptionKey($passwd, $salt);

See also: `Using Encryption and Authentication Correctly <https://paragonie.com/b/FmKm92tOMhEosukg>`_

.. note::

    All direct calls to Libsodium or other crypto related functions must be use
    "root-namespace" directive ``\password_verify`` not ``password_verify``.

.. _`hamming weight`: https://en.wikipedia.org/wiki/Hamming_weight

Unit testing with encryption
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Encrypting and decrypting data slows down the system, and provides another challenge
when working with expectations for results.

.. caution::

    Disabling encryption during tests introduces the risk of improper results
    during actual usage. Don't use mocked calls to the crypto engine.

    Use the actual encrypted data for tests, and provide tempered data for
    failure tests.

* Mark the (unit) test as ``@group slow`` to speed-up the overall testing suite;

* If encryption or cryptographic hashing is detail of the tested class,
  use a Faked encryption implementation. But always clearly state this
  implementation is insecure and for testing only!

TLS/SSL
-------

.. _tls-ssl:

TLS must be enabled by default.

* Tools must not allow to disable TLS! Inform the user when something
  goes wrong, and provide a link to a manual with more instructions.

  Don't add options like ``--insecure``, they only weaken the security
  and don't solve the actual problem.

* Hosting operations like: email, FTP and web access may allow to disable
  TLS, but must actively discourage this practice.

* Use strong ciphers https://wiki.mozilla.org/Security/Server_Side_TLS#Recommended_Ciphersuite

* Always link to a trusted source for recommended ciphers and configuration,
  don't hardcode them or keep them in the local documentation only (this gets outdated easily).

* Refuse the acceptance of expired or revoked certificates and keys.

* Enable peer-certificate verification.

* Disable compression (to prevent against BREACH and CRIME attacks).

* Use https://github.com/paragonie/certainty to ensure an up-to-date CA list.

.. tip::

    Thanks to LetsEncrypt and other free alternatives,
    LTS/SSL is now more accessible then ever.

HTTPS/HTTP2
~~~~~~~~~~~

These principles only apply the HTTP protocol over TLS or HTTP2.

* Enable `HTTP Strict Transport Security (HSTS)`_.

* Disallow mixed content, all content must be is served over TLS.

* Don't Recommend `Certificate and Public Key Pinning`_ (until all concerns with
  this technique are resolved).

.. _`HTTP Strict Transport Security (HSTS)`: https://tools.ietf.org/html/draft-hodges-strict-transport-sec-02
.. _`Certificate and Public Key Pinning`: https://www.owasp.org/index.php/Certificate_and_Public_Key_Pinning

Hash generating and comparison
------------------------------

* Don't write your own cryptography (including a hashing algorithm).

* Guard against a `Collision attack`_, use modern hashing techniques like sha256 or better.

* Sha1 is allowed for low collision cache data like a Redis or Memcache storage key.

* Use time-safe string comparison for cryptographic hashes (including passwords and checksum);
    * In PHP use `hash_equals() <http://php.net/manual/en/function.hash-equals.php>`_.
    * Use the `double-hmac-strategy`_ for languages that don't have a time safe string
      comparison method.

.. _`Collision attack`: https://en.wikipedia.org/wiki/Collision_attack
.. _`double-hmac-strategy`: https://paragonie.com/b/WS1DLx6BnpsdaVQW

REST API
--------

REST endpoints are an easy target for attacks, the OWASP has published
an `REST Security Cheat Sheet`_ with best practices and recommendations.

Any REST implementation MUST follow these recommendations.

The `Api-platform`_ is the recommended framework for realizing a secure REST functionality.
It follows the OWASP REST best practices as explained in: https://github.com/api-platform/core/blob/master/features/security/README.md

.. _`REST Security Cheat Sheet`: https://www.owasp.org/index.php/REST_Security_Cheat_Sheet
.. _`Api-platform`: https://github.com/api-platform/core/

Secure file upload
------------------

One popular attack method is uploading malicious files to a server.
Uploading could be provided for either support-ticket attachments,
or batch processing.

**Batch processing is a topic of it's own, ensure only accepted file formats
are passed trough, before processing.**

The first rule of secure file uploading is: don't allow anything that is not
usable to the context. *Don't allow uploading of image files for a XML document
processor.*

And don't trust what the user provides, the file's content is whats true.
*Filenames can be changed and user-provided mime-types can be spoofed.*

Requirements
~~~~~~~~~~~~

* Don't allow file uploading for unauthenticated users.

* Don't expose the internal storage location and filename to the user.
    * Unless the file is to be provided as-is (like a custom theme);
      **Use a very strict validation for unprotected file storage!**

* Encourage anti-virus scanning when possible (requires an scanner gateway).

* Always force a download, don't allow opening in the browser.

* Safe-list accepted mime-types (no forbidden-list).
    * Disallow any executable file including: exe, bat, cmd, reg, dmg.
    * *Archive files may be allowed as they provide a boundary before execution.*

* Favor a write/read-only filesystem (no execution possible).
    * *This requires a proper configuration by the sysadmin.*

* Get the actual mime-type based on the file-content.

* Don't use ``getimagesize`` to check if the file is a (save) image.

  You can upload a valid JPEG image and still hide a malicious payload
  in its EXIF comments. *Consider stripping EXIF data all together.*

**The other problem with file uploads is, well, downloading them safely.**

* SVG images, when accessed directly, will execute JavaScript code in the user's browser.
  This is true `despite the misleading image/ prefix in the MIME type <https://github.com/w3c/svgwg/issues/266>`_.

* MIME type sniffing can lead to type confusion attacks, as discussed previously.
  `See X-Content-Type-Options <https://paragonie.com/blog/2017/12/2018-guide-building-secure-php-software#security-headers>`_.

* If you forego the previous advice about how to store uploaded files safely,
  an attacker that manages to upload a .php or .phtml file may be able to
  execute arbitrary code by accessing the file directly in their browser,
  thereby giving them complete control over the server. Play it safe.

Requirements for sensitive data uploads
.......................................

* Check for proper authorization to: upload and view/download the file.

* Disallow caching by a Proxy or ensure authentication is checked.
    * Use a short cache lifetime, either 3 minutes to prevent DoS attacks.
    * Caching allows to restart quickly when the connection got lost.

Storage
~~~~~~~

*This excludes direct-public facing uploads, like custom themes.*

Storage must prevent direct access from users, as this could lead to disclosure
of information or execution of uploaded files. Only the application should be
able to read the file contents.

This is a process, bailout directly when a condition does not pass.

1. Validate all security conditions are correct.
    1. Validate the file is actually uploaded.
    2. Check user is authorized to upload, with the correct attributes
       (*no uploading attachments for ticket the user doesn't have access to*).
    3. Check file-extension (initial validation).
    4. Check actual mime-type based on the file-content (strict validation,
       PHP already provides the correct type).
    5. Optionally scan the file for viruses or malicious content.
2. Generate a unique id for the upload (either UUID), used to reference the upload.
3. Store the uploaded file under a unique-name (truly random, not UUID).
    * Store the unique file-name in the database, it must never be exposed publicly.
    * Store the: original filename (sanitized), actual mime-type,
      file size (KiB) and sha256-checksum in the database.
4. *Only use the UUID to referencing the file publicly.*

_Optionally file encryption can be considered, but this will also slows down
the download process, and thus should only be done for sensitive data._

See also: `How to Securely Allow Users to Upload Files <https://paragonie.com/b/rSm6jGTH83wT5roU>`_

SQL/Query and Database communication
------------------------------------

.. caution::

    Please be extra cautious in this section, SQL injections are a major risk.
    And introducing them in today's times is unacceptable.

In short, **DON'T EVER USE ``addslashes``!.** Use the correct driver provided
escaping functions. Don't build your own escaper.

**Restricting which characters may be used in a value, DOES NOT prevent against SQL injections.**

* Use proper escaping (Again. DON'T EVER USE ``addslashes``).

* Use prepared statements for SQL (see notes below).

* Avoid using dynamic SQL in db user-defined functions,
  be sure to use proper escaping when they *are* used.

* Always provide the required/allowed owner-ids when performing a search operation;
  Prevent returning of unauthorized results, during in the querying.

* Use role access separation for the various applications and background
  services (the email platform, web application, configuration-update daemon
  all have *there own* access/authentication role with explicit granting).

* Revoke all access to database objects from a role (except for the owner),
  and grant explicitly when access is needed.

* Ensure only the installer/upgrader, and system administrators can change the
  structure of the database. World accessible applications (like the web application)
  must not be able to change the structure and database configuration.

.. note::

    **Note on prepared statements:**

    Be sure when using PDO to disable ``PDO::ATTR_EMULATE_PREPARES`` as this is known
    to cause security problems. Only really old versions of the MySQL client required this.

    The RollerworksSearch system doesn't use prepared statements for SQL/DQL,
    but instead ensures a proper escaping of the value. This is an exceptional case.

Elasticsearch
~~~~~~~~~~~~~

Elasticsearch works with JSON to query the index for matching documents.

* Disallow to inject a structure directly from the outside, require explicit
  building of the structure. Use a RollerworksSearch ``SearchCondition`` instead.

* Don't generate the JSON structure using string concatenation.

* Protect the Elasticsearch installation from direct unauthorized access.

Password management
-------------------

First of: Don't modify the password (except for trimming surrounding whitespace),
don't remove any special characters or do case normalizing.

And don't forbid usage of specific characters. Including emoji's.

.. note::

    Limit passwords to 120 characters max, to prevent DoS attacks.

    *120 characters equals to roughly 1024 bit's of data. Trying to crack this
    will take more time then the average life time of a password.*

* Passwords should have a limited lifetime, keep the last-modification date-time
  of a password separate from the "main" last-modification date-time.

* Require the user to provide a new password when it's expired.
    * Don't allow the user to do anything else before the password is updated.
    * When passwords can expire, don't allow the re-usage of old passwords.

* To improve the UX, allow the user to "show" the password filled in the field.
  Provide a warning to the user about the risks.

* Don't refill a password field when the form contained errors.

* Don't refill a password field when modifying existing data, allow the field
  to be empty. And only validate *when* a value is provided.

* Show the a strength indication of the password, and fail when it's below
  a required minimum.

* Require the user to provide the current password when changing the password.

Storage
~~~~~~~

* Don't write your own password cryptography.

* Don't store the password, store a cryptographic hash of the password.

* Use bcrypt or when available use Halite/Argon2 (from libsodium).
  Keep the password-hash encryption key separate from the database.

* Don't use PBKDF2 for authentication, this algorithm was designed for
  key derivation, which can then be used as a cryptographic key.

* Use constant-time comparison (see `Hash generating and comparison`_).

* Never store a password in a readable format or encrypted (except for Halite Password).

* Check (after a successful authentication) if the password needs to be rehashed.
  And update it immediately.

See also: `How to Safely Store Your Users' Passwords <https://paragonie.com/b/gmBNidaAt4C2QxhO>`_

Password resetting
~~~~~~~~~~~~~~~~~~

When a user forgets a password, there is only one option: reset the password.
But as you have guessed, the user can't do this without a password.

.. caution::

    **This bears emphasis:** When you give your users the capability to reset
    their password, you are creating a backdoor into their account.

For all security recommendations please see:

https://paragonie.com/blog/2016/09/untangling-forget-me-knot-secure-account-recovery-made-simple

.. note::

    Security questions aren't a good idea, they should only be used to harden
    the verification process (prevent sending unwanted reset emails).

No matter which technique is used (email, SMS, postal code) it's important
to first verify the reset is requested by the user and not by an attacker.

Even when the attacker is not able to intercept the reset link.
The user is bothered with unwanted emails, or SMS messages (*at 2:00 AM*).

To prevent this from happening the following requirements must be followed:

* Only allow to send a new reset per user, every n minutes/hours (either 10 minutes).
* Log every reset request (with IP address).
* Block access when to many attempts are made within a period
  (say 5 attempts from the same IP every 10 minutes).
* Ask some information only the user knows (either a security question, see notes below).
* Require to solve a (re)CATCHA after 3 failed attempts.

Once the identity of the user is verified continue with the next step.

1. Email (traditional method):
    * Email message with a link to provide a one-time login.
    * Token link can only be used once, once visited the token expires within 5 minutes.
    * Token expires after 20 minutes.
    * Allow to configure a GnuPG public key for encryption.

**Keep the following in mind during the reset:**

* Security token/code must be cryptographically random.

* Check IP of the reset request equals the IP during the actual reset.

* Use a cryptographically random token and a separate auth-code (see untangling-forget-me-knot for details);
  Thread auth-code as a password, it must be properly hashed.

* Don't give an indication about the existence of the user
  (unless proper answers were provided or access is blocked for the visitor):

  * 'No such user with this email address or wrong security questions provided.' (not recommended, but better then nothing);

  * 'To many attempts from this IP address, please try again later.';

  * 'An email has been send, please follow the instructions provided.';

  * 'An text message has been send to your mobile phone, please type the received recover-code below.';

Once the user is authenticated do the following:

#. Require to provide a new password. Don't allow the user to do anything else before the password is updated;

#. Once the password is updated, inform the user by email, the password was reset;

#. Regenerate the session-id (and destroy the old one);

#. Expire all active sessions;

See also: `Forgot Password Cheat Sheet <https://www.owasp.org/index.php/Forgot_Password_Cheat_Sheet>`_

.. note::

    The Park-Manager user system doesn't perform an actual authentication during a reset
    operation. *After the password is successfully reset the user needs to login with the
    new password.*

Security questions
..................

Any security questions or identity information presented to users to reset
forgotten passwords should ideally have the following four characteristics:

* **Memorable**: If users can't remember their answers to their security
  questions, you have achieved nothing.

* **Consistent**: The user's answers should not change over time.
  For instance, asking "What is the name of your significant other?"
  may have a different answer 5 years from now.

* **Nearly universal**: The security questions should apply to a wide an
  audience as possible.

* **Safe**: The answers to security questions should not be something that
  is easily guessed, or researched (either, something that is matter of
  public record).

**Requirements:**

* Use at least three separate questions.

* Normalize casing and spaces (only remove leading and pending space characters).

* Be explicit about the question and format, provide an example for formats 'either January 1900'.

* Cryptographically hash the answers, they may contain sensitive information
  (see `Hash generating and comparison`_).

* Allow one custom question and answer (user's choice to use or not).

* Don't hardcode the list, but let the Administrator configure a list manually.
    * Allow to configure constraints for an answer (either only numbers, pattern, etc.)
    * Allow localization support, also allow localized constraints (hh:mm vs hh:mm [am|pm]).
    * Allow to mark a question as "removed", will only be shown during a reset.
      But cannot be used anymore.

See also:

* `goodsecurityquestions.com <http://goodsecurityquestions.com/designing>`_
* `Choosing and Using Security Questions Cheat Sheet <https://www.owasp.org/index.php/Choosing_and_Using_Security_Questions_Cheat_Sheet>`_

**Examples:**

* What was the house number and street name you lived in as a child?

* What were the last four digits of your childhood telephone number?

* What primary school did you attend?

* In what town or city was your first full time job?

* In what town or city did you meet your spouse/partner?

* What is the middle name of your oldest child?

* What are the last five digits of your driver's license number?

* What is your grandmother's (on your mother's side) maiden name?

* What is your spouse or partner's mother's maiden name?

* In what town or city did your mother and father meet?

* What time of the day were you born? (hh:mm)

* What time of the day was your first child born? (hh:mm)

* What is your oldest sibling’s birthday month and year? (either, January 1900)
