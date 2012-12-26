require 'date'
require 'digest/md5'
require 'fileutils'

basedir = "."
build   = "#{basedir}/build"
source  = "#{basedir}/library"

desc "Task used by Jenkins-CI"
task :jenkins => [:prepare, :lint, :installdep, :test, :apidocs, :phploc, :phpcs_ci, :phpcb, :phpcpd, :pdepend, :phpmd, :phpmd_html]

desc "Task used by Travis-CI"
task :travis => [:installdep, :test]

desc "Default task"
task :default => [:lint, :installdep, :test, :phpcs, :apidocs, :readthedocs]

desc "Run tests"
task :test => [:phpunit, :behat]

desc "Spell check and generate end user docs"
task :readthedocs do
  wd = Dir.getwd
  Dir.chdir("docs")
  begin
    sh %{make spelling}
  rescue Exception
    puts "Spelling error in the docs, aborting"
    exit 1
  end
  puts "No spelling errors. Generate docs"
  sh %{make html}
  Dir.chdir(wd)
end

desc "Clean up and create artifact directories"
task :prepare do
  FileUtils.rm_rf build
  FileUtils.mkdir build

  ["coverage", "logs", "docs", "code-browser", "pdepend"].each do |d|
    FileUtils.mkdir "#{build}/#{d}"
  end
end

desc "Install dependencies"
task :installdep do
  if ENV["TRAVIS"] == "true"
    system "composer --no-ansi install --dev"
  else
    Rake::Task["install_composer"].invoke
    system "php -d \"apc.enable_cli=0\" composer.phar install --dev"
  end
end

desc "Update dependencies"
task :updatedep do
  Rake::Task["install_composer"].invoke
  system "php -d \"apc.enable_cli=0\" composer.phar update --dev"
end

desc "Install/update composer itself"
task :install_composer do
  if File.exists?("composer.phar")
    system "php -d \"apc.enable_cli=0\" composer.phar self-update"
  else
    system "curl -s http://getcomposer.org/installer | php -d \"apc.enable_cli=0\""
  end
end

desc "Generate checkstyle.xml using PHP_CodeSniffer"
task :phpcs_ci do
  system "phpcs --report=checkstyle --report-file=#{build}/logs/checkstyle.xml --standard=Imbo #{source}"
end

desc "Check CS"
task :phpcs do
  system "phpcs --standard=Imbo #{source}"
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

desc "Generate API documentation using phpdoc"
task :apidocs do
  system "phpdoc -d #{source} -t #{build}/docs"
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

desc "Run PHPUnit tests"
task :phpunit do
  if ENV["TRAVIS"] == "true"
    system "sudo apt-get install -y php5-sqlite libmagickcore-dev libjpeg-dev libdjvulibre-dev libmagickwand-dev"

    ini_file = Hash[`php --ini`.split("\n").map {|l| l.split(/:\s+/)}]["Loaded Configuration File"]

    {"imagick" => "3.1.0RC2", "mongo" => "1.3.1", "memcached" => "2.0.1", "APC" => "3.1.12"}.each { |package, version|
      filename = "#{package}-#{version}.tgz"
      system "wget http://pecl.php.net/get/#{filename}"
      system "tar -xzf #{filename}"
      system "sh -c \"cd #{filename[0..-5]} && phpize && ./configure && make && sudo make install\""
      system "sudo sh -c \"echo 'extension=#{package.downcase}.so' >> #{ini_file}\""
    }

    system "sudo sh -c \"echo 'apc.enable_cli=on' >> #{ini_file}\""

    begin
      sh %{vendor/bin/phpunit --verbose -c phpunit.xml.travis}
    rescue Exception
      exit 1
    end
  else
    begin
      sh %{vendor/bin/phpunit --verbose}
    rescue Exception
      exit 1
    end
  end
end

desc "Run functional tests"
task :behat do
  begin
    sh %{vendor/bin/behat --strict}
  rescue Exception
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
task :publish_api_docs do
    system "git checkout master"
    Rake::Task["apidocs"].invoke
    wd = Dir.getwd
    Dir.chdir("/home/christer/dev/imbo-ghpages")
    system "git pull origin gh-pages"
    system "cp -r #{wd}/build/docs/* ."
    system "git add --all"
    system "git commit -a -m 'Updated API docs [ci skip]'"
    system "git push origin gh-pages"
    Dir.chdir(wd)
end

desc "Release a new version"
task :release, :version do |t, args|
  version = args[:version]

  if /^[\d]+\.[\d]+\.[\d]+$/ =~ version
    # Syntax check
    Rake::Task["lint"].invoke

    # Unit tests
    Rake::Task["test"].invoke

    # Generate end-user docs
    Rake::Task["readthedocs"].invoke

    # Tag the current state of master and push to GitHub
    Rake::Task["github"].invoke(version)

    # Update the API docs and push to gh-pages
    Rake::Task["publish_api_docs"].invoke
  else
    puts "'#{version}' is not a valid version"
    exit 1
  end
end
