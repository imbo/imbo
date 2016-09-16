require 'date'

basedir  = "."
build    = "#{basedir}/build"
source   = "#{basedir}/src"
tests    = "#{basedir}/tests"

desc "Task used by Jenkins-CI"
task :jenkins => [:test, :apidocs, :phpcs_ci]

desc "Default task"
task :default => [:test, :phpcs, :apidocs, :readthedocs]

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

desc "Generate checkstyle.xml using PHP_CodeSniffer"
task :phpcs_ci do
  system "phpcs --report=checkstyle --report-file=#{build}/logs/checkstyle.xml --standard=Imbo #{source}"
end

desc "Check CS"
task :phpcs do
  system "phpcs --standard=Imbo #{source}"
end

desc "Generate jdepend.xml and software metrics charts using PHP_Depend"
task :pdepend do
  system "pdepend --jdepend-xml=#{build}/logs/jdepend.xml --jdepend-chart=#{build}/pdepend/dependencies.svg --overview-pyramid=#{build}/pdepend/overview-pyramid.svg #{source}"
end

desc "Generate API documentation using phpdoc"
task :apidocs do
  system "phpdoc -d #{source} -t #{build}/docs --title \"Imbo API docs\""
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
