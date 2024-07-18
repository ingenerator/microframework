# Blackbox tests

These black-box tests provide the majority of the package coverage, to make sure that it works as expected when fully
wired up and serving HTTP traffic. This is because most of what it does is parsing global state, setting headers,
rendering output, etc which would be hard and brittle to test with unit or integration tests.

The tests are run against the **built version of the package**. To do this, we use `git export` to build a distribution
package, then use composer to install that with its dependencies inside a docker container running apache.

Note that this means when working locally, changes to the application code **will not be picked up by the test_subject 
automatically**. To update the test_subject you will need to **commit your changes** (so that they are included in the
`git export HEAD`) and then **exit and re-provision the blackbox testing stack**.

The blackbox tests run in a separate container, which has three couplings to the system under test:

* Over HTTP to actually make requests and execute request handlers.
* With a shared volume (under test/blackbox/implementation/htdocs/dynamic) to provision handler code for specific
  test cases.
* Via a third container, which accepts the test subject container logs from docker over syslog, and dumps them to a file
  that test cases can read for asserting log output generated during a test (see the StackdriverLoggingTest for 
  examples).

This stack is configured and provisioned with docker compose:
- To run the tests once, use `test/run-blackbox-tests.sh`. This is the script that runs in CI.
- To work on tests, use `test/run-blackbox-tests-interactive.sh`. This will spin up the stack and give you a shell on
  the test runner, where you can then `vendor/bin/phpunit test/blackbox/` to run one or more testcases. Testcases are 
  mounted in, so local changes will apply on the next re-run. However changes to the application will require you to 
  exit, commit, and re-run `test/run-blackbox-tests-interactive.sh` to re-provision the docker-compose environment.
