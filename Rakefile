require 'date'
require 'digest/md5'
require 'fileutils'
require 'nokogiri'

basedir = "."
build   = "#{basedir}/build"
source  = "#{basedir}/library"

desc "Task used by Jenkins-CI"
task :jenkins => [:prepare, :lint, :test, :apidocs, :phploc, :phpcs, :phpcb, :phpcpd, :pdepend, :phpmd, :phpmd_html]

desc "Task used by Travis-CI"
task :travis => [:test]

desc "Default task"
task :default => [:lint, :test]

desc "Clean up and create artifact directories"
task :prepare do
  FileUtils.rm_rf build
  FileUtils.mkdir build

  ["coverage", "logs", "docs", "code-browser", "pdepend"].each do |d|
    FileUtils.mkdir "#{build}/#{d}"
  end
end

desc "Generate checkstyle.xml using PHP_CodeSniffer"
task :phpcs do
  system "phpcs --report=checkstyle --report-file=#{build}/logs/checkstyle.xml --standard=Imbo #{source}"
end

desc "Aggregate tool output with PHP_CodeBrowser"
task :phpcb do
  system "phpcb --log #{build}/logs --source #{source} --output #{build}/code-browser"
end

desc "Generate pmd-cpd.xml using PHPCPD"
task :phpcpd do
  system "phpcpd --log-pmd #{build}/logs/pmd-cpd.xml #{source}"
end

desc "Generate jdepend.xml and software metrics charts using PHP_Depend"
task :pdepend do
  system "pdepend --jdepend-xml=#{build}/logs/jdepend.xml --jdepend-chart=#{build}/pdepend/dependencies.svg --overview-pyramid=#{build}/pdepend/overview-pyramid.svg #{source}"
end

desc "Generate pmd.xml using PHPMD (configuration in phpmd.xml)"
task :phpmd do
  system "phpmd #{source} xml #{basedir}/phpmd.xml --reportfile #{build}/logs/pmd.xml"
end

desc "Generate pmd.html using PHPMD (configuration in phpmd.xml)"
task :phpmd_html do
  system "phpmd #{source} html #{basedir}/phpmd.xml --reportfile #{build}/logs/pmd.html"
end

desc "Generate phploc data"
task :phploc do
  system "phploc --log-csv #{build}/logs/phploc.csv --log-xml #{build}/logs/phploc.xml #{source}"
end

desc "Generate API documentation using phpdoc (config in phpdoc.xml)"
task :apidocs do
  system "phpdoc"
end

desc "Check syntax on all php files in the project"
task :lint do
  `git ls-files "*.php"`.split("\n").each do |f|
    begin
      sh %{php -l #{f}}
    rescue Exception
      exit 1
    end
  end
end

desc "Run PHPUnit tests (config in phpunit.xml)"
task :test do
  if ENV["TRAVIS"] == "true"
    system "sudo apt-get install -y php5-sqlite libmagickcore-dev libjpeg-dev libdjvulibre-dev libmagickwand-dev"

    ini_file = Hash[`php --ini`.split("\n").map {|l| l.split(/:\s+/)}]["Loaded Configuration File"]

    ["imagick", "mongo", "memcached", "APC"].each { |e|
      system "wget http://pecl.php.net/get/#{e}"
      system "tar -xzf #{e}"
      system "sh -c \"cd #{e}-* && phpize && ./configure && make && sudo make install\""
      system "sudo sh -c \"echo 'extension=#{e.downcase}.so' >> #{ini_file}\""
    }

    system "curl -s http://getcomposer.org/installer | php"
    system "php composer.phar --no-ansi install"

    system "sudo sh -c \"echo 'apc.enable_cli=on' >> #{ini_file}\""

    puts "Opening phpunit.xml.dist"
    document = Nokogiri::XML(File.open("phpunit.xml.dist"))
    document.xpath("//phpunit/php/var[@name='MEMCACHED_HOST']").first["value"] = "127.0.0.1"
    document.xpath("//phpunit/php/var[@name='MEMCACHED_PORT']").first["value"] = "11211"
    document.xpath("//phpunit/logging").remove

    puts "Writing edited version of phpunit.xml"
    File.open("phpunit.xml", "w+") do |f|
        f.write(document.to_xml)
    end
  end

  if File.exists?("phpunit.xml")
    begin
      sh %{phpunit --verbose -c phpunit.xml}
    rescue Exception
      exit 1
    end
  else
    puts "phpunit.xml does not exist"
    exit 1
  end
end

desc "Create a PEAR package"
task :pear, :version do |t, args|
  version = args[:version]

  if /^[\d]+\.[\d]+\.[\d]+$/ =~ version
    now  = DateTime.now
    hash = Digest::MD5.new

    xml = Nokogiri::XML::Builder.new { |xml|
      xml.package(:version => "2.0", :xmlns => "http://pear.php.net/dtd/package-2.0", "xmlns:tasks" => "http://pear.php.net/dtd/tasks-1.0", "xmlns:xsi" => "http://www.w3.org/2001/XMLSchema-instance", "xsi:schemaLocation" => ["http://pear.php.net/dtd/tasks-1.0", "http://pear.php.net/dtd/tasks-1.0.xsd", "http://pear.php.net/dtd/package-2.0", "http://pear.php.net/dtd/package-2.0.xsd"].join(" ")) {
        xml.name "Imbo"
        xml.channel "pear.starzinger.net"
        xml.summary "RESTful image server"
        xml.description "Imbo is a RESTful image server written in PHP. Imbo lets you store original hi-res images along with optional metadata and enables you to fast and easily fetch variations of the originals (thumbnails, resized versions, cropped versions and so forth)."
        xml.lead {
          xml.name "Christer Edvartsen"
          xml.user "christeredvartsen"
          xml.email "cogo@starzinger.net"
          xml.active "yes"
        }
        xml.developer {
          xml.name "Espen Hovlandsdal"
          xml.user "rexxars"
          xml.email "espen@hovlandsdal.com"
          xml.active "yes"
        }
        xml.date now.strftime('%Y-%m-%d')
        xml.time now.strftime('%H:%M:%S')
        xml.version {
          xml.release version
          xml.api version
        }
        xml.stability {
          xml.release "beta"
          xml.api "beta"
        }
        xml.license "MIT", :uri => "http://www.opensource.org/licenses/mit-license.php"
        xml.notes "http://github.com/imbo/imbo/blob/#{version}/README.markdown"
        xml.contents {
          xml.dir(:name => "/", :baseinstalldir => "Imbo") {
            # Library files
            `git ls-files library public config|grep -v Version.php`.split("\n").each { |f|
              xml.file(:md5sum => hash.hexdigest(File.read(f)), :role => "php", :name => f)
            }

            # Add a replace task to the Version.php file
            xml.file(:md5sum => hash.hexdigest(File.read("library/Imbo/Version.php")), :role => "php", :name => "library/Imbo/Version.php") {
              xml["tasks"].replace(:from => "@package_version@", :to => "version", :type => "package-info")
            }

            # Doc files
            ["README.markdown", "LICENSE", "ChangeLog.markdown"].each { |f|
              xml.file(:md5sum => hash.hexdigest(File.read(f)), :role => "doc", :name => f)
            }
          }
        }
        xml.dependencies {
          xml.required {
            xml.php {
              xml.min "5.3.2"
            }
            xml.pearinstaller {
              xml.min "1.9.0"
            }
            xml.extension {
              xml.name "spl"
            }
            xml.extension {
              xml.name "imagick"
            }
            xml.extension {
              xml.name "mongo"
              xml.min "1.2.10"
            }
          }
          xml.optional {
            xml.package {
              xml.name "DoctrineDBAL"
              xml.channel "http://pear.doctrine-project.org"
              xml.min "2.2.2"
            }
            xml.extension {
              xml.name "memcached"
            }
            xml.extension {
              xml.name "apc"
            }
          }
        }
        xml.phprelease
      }
    }

    # Write XML to package.xml
    File.open("package.xml", "w") { |f|
      f.write(xml.to_xml)
    }

    # Generate pear package
    system "pear package"

    # Remove tmp files
    File.unlink("package.xml")
  else
    puts "'#{version}' is not a valid version"
    exit 1
  end
end

desc "Publish a PEAR package to pear.starzinger.net"
task :publish, :version do |t, args|
  version = args[:version]

  if /^[\d]+\.[\d]+\.[\d]+$/ =~ version
    package = "Imbo-#{version}.tgz"

    if File.exists?(package)
      wd = Dir.getwd
      system "pirum add /home/christer/dev/christeredvartsen.github.com #{package}"
      Dir.chdir("/home/christer/dev/christeredvartsen.github.com")
      system "git add --all"
      system "git commit -a -m 'Added #{package[0..-5]}'"
      system "git push"
      Dir.chdir(wd)
      File.unlink(package)
    else
      puts "#{package} does not exist. Run the pear task first to create the package"
    end
  else
    puts "'#{version}' is not a valid version"
    exit 1
  end
end

desc "Tag current state of the master branch and push it to GitHub"
task :github, :version do |t, args|
  version = args[:version]

  if /^[\d]+\.[\d]+\.[\d]+$/ =~ version
    system "git checkout master"
    system "git merge develop"
    system "git tag #{version}"
    system "git push"
    system "git push --tags"
  else
    puts "'#{version}' is not a valid version"
    exit 1
  end
end

desc "Publish API docs"
task :docs do
    system "git checkout master"
    system "phpdoc"
    wd = Dir.getwd
    Dir.chdir("/home/christer/dev/imbo-ghpages")
    system "git pull origin gh-pages"
    system "cp -r #{wd}/build/docs/* ."
    system "git add --all"
    system "git commit -a -m 'Updated API docs [ci skip]'"
    system "git push origin gh-pages"
    Dir.chdir(wd)
end

desc "Release a new version (builds PEAR package, updates PEAR channel and pushes tag to GitHub)"
task :release, :version do |t, args|
  version = args[:version]

  if /^[\d]+\.[\d]+\.[\d]+$/ =~ version
    # Syntax check
    Rake::Task["lint"].invoke

    # Unit tests
    Rake::Task["test"].invoke

    # Build PEAR package
    Rake::Task["pear"].invoke(version)

    # Publish to the PEAR channel
    Rake::Task["publish"].invoke(version)

    # Tag the current state of master and push to GitHub
    Rake::Task["github"].invoke(version)

    # Update the API docs and push to gh-pages
    Rake::Task["docs"].invoke
  else
    puts "'#{version}' is not a valid version"
    exit 1
  end
end
