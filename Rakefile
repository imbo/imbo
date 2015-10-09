require 'date'
require 'digest/md5'
require 'fileutils'
require 'json'

basedir  = "."
build    = "#{basedir}/build"
source   = "#{basedir}/library"
tests    = "#{basedir}/tests"

desc "Task used by Jenkins-CI"
task :jenkins => [:prepare, :lint, :installdep, :test, :apidocs, :phploc, :phpcs_ci, :phpcb, :phpcpd, :pdepend, :phpmd, :phpmd_html]

desc "Default task"
task :default => [:lint, :installdep, :test, :phpcs, :apidocs, :readthedocs]

desc "Run tests"
task :test => [:phpunit, :behat]

desc "Run tests without code coverage"
task :test_no_cc do
  begin
    sh %{vendor/bin/phpunit --verbose -c tests/phpunit}
    sh %{vendor/bin/behat --strict --config tests/behat/behat.yml --profile=no-cc}
  rescue Exception
    exit 1
  end
end

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
  Rake::Task["install_composer"].invoke
  system "php -d \"apc.enable_cli=0\" composer.phar -n install --prefer-source"
end

desc "Update dependencies"
task :updatedep do
  Rake::Task["install_composer"].invoke
  system "php -d \"apc.enable_cli=0\" composer.phar -n update --prefer-source"
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
  system "phpdoc -d #{source} -t #{build}/docs --title \"Imbo API docs\""
end

desc "Check syntax on all php files in the project"
task :lint do
  lintCache = "#{basedir}/.lintcache"

  begin
    sums = JSON.parse(IO.read(lintCache))
  rescue Exception => foo
    sums = {}
  end

  `git ls-files "*.php"`.split("\n").each do |f|
    f = File.absolute_path(f)
    md5 = Digest::MD5.hexdigest(File.read(f))

    next if sums[f] == md5

    sums[f] = md5

    begin
      sh %{php -l #{f}}
    rescue Exception
      exit 1
    end
  end

  IO.write(lintCache, JSON.dump(sums))
end

desc "Run PHPUnit tests"
task :phpunit do
  begin
    sh %{vendor/bin/phpunit --verbose -c tests/phpunit --coverage-html build/coverage --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml}
  rescue Exception
    exit 1
  end
end

desc "Run functional tests"
task :behat do
  begin
    sh %{vendor/bin/behat --strict --config tests/behat/behat.yml}
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
  else
    puts "'#{version}' is not a valid version"
    exit 1
  end
end
