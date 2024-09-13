..  include:: /Includes.rst.txt


..  _known-problems:

==============
Known Problems
==============

I'm missing new wall posts
==========================

`walls_io_proxy` was was designed to cache wall posts. This is useful, if API
of walls.io is offline. Please check the cache invalidation property in page
property. Instead of caching the page content for 8 hours try values like
5 or 15 minutes.

Here is the enhanced "Known Issues" page, including the updated database
modification queries and information for TYPO3 v12:

Emoji Handling
==============

### Description

When content retrieved from the service includes emojis, it can cause issues
because the default connection charset and the database tables' collation are
set to `utf8`. The `utf8` charset in MySQL cannot handle 4-byte Unicode
characters, which include many emojis, leading to potential data truncation
or errors.

### Affected Versions

All versions up to the current release

### Workaround

To handle emojis correctly, you need to switch your database and connection
settings from `utf8` to `utf8mb4`. The `utf8mb4` character set is specifically
designed to handle 4-byte Unicode characters.

#### Steps to Resolve

1. **Update MySQL Database Charset and Collation**

   Run the following SQL command to update the database charset:

   ```sql
   ALTER DATABASE your_database_name CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
   ```

   Update each table's charset:

   ```sql
   ALTER TABLE index_phash CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ALTER TABLE index_fulltext CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   Update each text column's charset:

   ```sql
   ALTER TABLE index_phash CHANGE your_column_name your_column_name TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ALTER TABLE index_fulltext CHANGE your_column_name your_column_name TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Update TYPO3 Connection Configuration**

    Update the database connection settings in TYPO3 to use `utf8mb4`:

    - **For TYPO3 v9 and earlier:**

        Edit your `LocalConfiguration.php` file:

        ```php
        'DB' => [
            'Connections' => [
                'Default' => [
                    'driver' => 'mysqli',
                    'dbname' => 'your_database_name',
                    'user' => 'your_database_user',
                    'password' => 'your_database_password',
                    'host' => 'your_database_host',
                    'port' => 3306,
                    'charset' => 'utf8mb4',
                    'tableoptions' => [
                        'charset' => 'utf8mb4',
                        'collate' => 'utf8mb4_unicode_ci',
                    ],
                ],
            ],
        ],
        ```

    - **For TYPO3 v10 and v11:**

        Edit your `AdditionalConfiguration.php` file:

        ```php
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['charset'] = 'utf8mb4';
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['tableoptions']['charset'] = 'utf8mb4';
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['tableoptions']['collate'] = 'utf8mb4_unicode_ci';
        ```

    - **For TYPO3 v12:**

        Edit your `additional.php` file:

        ```php
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['charset'] = 'utf8mb4';
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['tableoptions']['charset'] = 'utf8mb4';
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['tableoptions']['collate'] = 'utf8mb4_unicode_ci';
        ```

        Edit your configuration file in `config/system/settings.php`:

        ```php
        return [
            'DB' => [
                'Connections' => [
                    'Default' => [
                        'charset' => 'utf8mb4',
                        'tableoptions' => [
                            'charset' => 'utf8mb4',
                            'collate' => 'utf8mb4_unicode_ci',
                        ],
                    ],
                ],
            ],
        ];
        ```

3. **Verify the Changes**

    Ensure your database and tables are set to `utf8mb4`, and test your
    extension thoroughly to confirm that it can handle emojis correctly.

---

This should help ensure your TYPO3 extension can handle emojis properly by
using the `utf8mb4` character set.
