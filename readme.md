![DelightfulDB Logo](docs/logo.png)

# DelightfulDB

A Minimalistic, Performant, and Indexable NoSQL Document Store For PHP

## Preface
I built this system while I was working on my recipe system and realized a lack of easy to deploy and performant options for small and hobbyist projects.

You may ask yourself why not just use a off-the-shelf database or document solution like MariaDB/MySQL/PostgreSQL, Mongo, CouchDB, ElasticSearch/SOLR/Lucene? ***The problem is infrastructure cost***. For small projects spinning up these services is a massive cost for both time and money. Furthermore there is infrastructure overhead you have to deal with when running those services such as making sure firewalls, ACLs/IAMs, storage, etc is configured, When you just need a basic document store that is performant without the need for having to run and manage these external services, you save money on infrastructure. Furthermore, because the system is based off of simple JSON files you can easily migrate these to another system without issue if you outgrow this solution.

I want to note that I'm fully aware that [SleekDB](https://github.com/rakibtg/SleekDB) exists. However, it wasn't what I was ultimately looking for since that database focuses more on active caching and table scans vs proactive document indexing. It serves an entirely different use case in my opinion.

I also want to note there are various NodeJS document store systems available as well, but with the nature of `npm` and the security risks related to the NodeJS ecosystem and their maintainers, ***I have no desire to run it on a publicly facing server***. However, you do you.

## System Requirements
- A Unix-Like Environment (Linux, MacOS)
- PHP 7.4+
- Unix tools like `awk` and `cat`
- Solid State Storage
  - **Note:** Most hosting instances like DigitalOcean, Rackspace, and AWS already do for almost all of their instances.
- Composer (Optional)

## Getting Started
You can read the docs here:
- [Installation](docs/install.md)
- [Configuration, Initialization, and Error Handling](docs/configuration.md)
- [Usage](docs/usage.md)
- [Driver Encapsulation](docs/encapsulation.md)

## Use Cases
**Ideally if you're doing something at an enterprise scale, you should look at something else.** That is not what this database is for. However, if you're doing something smaller this could work for your project. Here are some examples:

- Static Website Content or a Custom CMS
- Contacts / User Database Frontends
- Recipe Database Frontends
- Notes Application
- Local Caching Application
- Movie / Media Database Frontends

## Features
- Dependency Free
- Easy to install and portable. Does not use PSR-4 autoloading (*by design, I feel it is too opinionated*).
- Simple to understand functions that don't rely on an ORM-style query builder.
- A mostly schema-less document store. (*Schemas are only used for index definitions*)
- A true fulltext search engine.
- Field indexing for fast and efficient return results.
- Generator functions provided for memory efficiency.
- Indexed distinct selectors.
- Verbose and understandable error handling through exceptions.
- Code is clean and documented.

## Limitations
The system has some limitations. Part of the appeal is the engine's minimalism. However, if these limitations are a breaking feature for you, I encourage you to look elsewhere.

 - No Query Level Sorting
   - The expectation here is that you sort your documents at the application level, ideally using a middleware between the database driver and your application code.
- No Table Scanning
  - To keep the system read performant, searches are only done using fulltext indexing, defined indexes, and ids. This is to prevent memory usage and disk reads from slowing down the system or even causing a process crash.
- Single Index Field Search
  - There are no complex `joins`, `wheres`, `unions`, etc. This searches the document store by index on a single field at a time.
- No-Built In Relations
  - Honestly, this is a feature of an RDB systems, this has no place here in my honest opinion.
- Store-Level Locking
  - Since this engine is designed for small applications, there is no row-level locking or transactional logging. Writes lock the store until a timeout or the write process is completed synchronously.
- Filesystem Inode Concerns
  - Depending on how your have your filesystem configured, using this database for lots of documents (like millions) could cause you to run out of inodes. This is more common on inode provisioned filesystems like NetApp Wafl. **However all filesystems have limits**, for example the default for `ext4` is (by default) has 1 inode per 16k of storage and `apfs` has an insane 9 quintillion inode count. I would say if you're concerned about this at all, you should use something else.

## Feature Requests and Issues Policy
The policy for this is simple.

- Issues relating to security or bugs are accepted and will be addressed accordingly.
- Issues relating to performance will be on a case-by-case basis depending on the severity (or if it's in scope).
- Issues relating to PHP versions at or above 7.4 will be addressed, anything below will be immediately rejected.
- Issues relating to code quality will be reviewed.
- Features relating to anything in the limitations section will be immediately closed as these were the design choices I made. Feel free to fork and write your own database engine based off mine if you need something.
- Features I will consider are things that might be useful in the scope of this database without a ton of additional code or cpu/memory/disk overhead. The point is minimalism, not make a full SQL replacement.
- PRs will be immediately rejected if there isn't an associated issue to them.

My time is limited, if you're interested in becoming a maintainer make an issue ticket and we will go from there.

## Update Policy
In general, this likely won't get updated as frequently as other projects. **This does not mean it has been abandoned**, it's just the nature of the engine- after a certain point there will likely just be updates to ensure compatibility with newer versions of PHP. If this does get abandoned I will make a note on this readme and archive this repo.

## Changelog
Application changes can be found [here](changelog.md).

## License
This system uses the GNU GPLv3 to prevent corpo miscreants from yanking my code without contributing. You can read the license [here](license.md).
