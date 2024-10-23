import jetbrains.buildServer.configs.kotlin.*
import jetbrains.buildServer.configs.kotlin.buildFeatures.PullRequests
import jetbrains.buildServer.configs.kotlin.buildFeatures.buildCache
import jetbrains.buildServer.configs.kotlin.buildFeatures.commitStatusPublisher
import jetbrains.buildServer.configs.kotlin.buildFeatures.perfmon
import jetbrains.buildServer.configs.kotlin.buildFeatures.pullRequests
import jetbrains.buildServer.configs.kotlin.buildSteps.dockerCommand
import jetbrains.buildServer.configs.kotlin.buildSteps.script
import jetbrains.buildServer.configs.kotlin.triggers.vcs
import jetbrains.buildServer.configs.kotlin.vcs.GitVcsRoot

/*
The settings script is an entry point for defining a TeamCity
project hierarchy. The script should contain a single call to the
project() function with a Project instance or an init function as
an argument.

VcsRoots, BuildTypes, Templates, and subprojects can be
registered inside the project using the vcsRoot(), buildType(),
template(), and subProject() methods respectively.

To debug settings scripts in command-line, run the

    mvnDebug org.jetbrains.teamcity:teamcity-configs-maven-plugin:generate

command and attach your debugger to the port 8000.

To debug in IntelliJ Idea, open the 'Maven Projects' tool window (View
-> Tool Windows -> Maven Projects), find the generate task node
(Plugins -> teamcity-configs -> teamcity-configs:generate), the
'Debug' option is available in the context menu for the task.
*/

version = "2024.03"

project {

    vcsRoot(DeployVcsRoot)
    vcsRoot(AutomatedTestVcsRoot)

    buildType(AutomatedTest)
    buildType(Deploy)
}

object AutomatedTest : BuildType({
    name = "automated test"

    vcs {
        root(AutomatedTestVcsRoot)
    }

    steps {
        script {
            name = "build lib intermediária"
            id = "build_lib_intermediaria"
            scriptContent = "docker build -t backend-shared:latest ./shared"
            formatStderrAsError = true
        }
        script {
            name = "removendo .dockerignore"
            id = "removendo_dockerignore"
            scriptContent = "find . -name '*.dockerignore' -type f -delete"
            formatStderrAsError = true
        }
        script {
            name = "shared74"
            id = "test_pdo_cast-adm-api"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --build --rm shared74"
            formatStderrAsError = true
        }
        script {
            name = "shared83"
            id = "test_pdo_cast-83"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --build --rm shared83"
            formatStderrAsError = true
        }
        script {
            name = "adm-api"
            id = "test_adm_api"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --build --rm adm-api"
            formatStderrAsError = true
        }
        script {
            name = "load-balancer"
            id = "test_load_balancer"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --build --rm load-balancer"
            formatStderrAsError = true
        }
        script {
            name = "lookpay-api"
            id = "test_lookpay_api"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --build --rm lookpay-api"
            formatStderrAsError = true
        }
        script {
            name = "med-api"
            id = "test_med_api"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --build --rm med-api"
            formatStderrAsError = true
        }
        script {
            name = "wc-lookpay-credit-card"
            id = "test_wc_lookpay_credit_card"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --build --rm wc-lookpay-credit-card"
            formatStderrAsError = true
        }
    }

    triggers {
        vcs {
        }
    }

    features {
        perfmon {
        }
        pullRequests {
            vcsRootExtId = "${AutomatedTestVcsRoot.id}"
            provider = github {
                authType = token {
                    token = "credentialsJSON:0187b8ea-ad9f-4227-a350-7558d85cb876"
                }
                filterTargetBranch = "+:refs/heads/main"
                filterAuthorRole = PullRequests.GitHubRoleFilter.MEMBER
            }
        }
        commitStatusPublisher {
            vcsRootExtId = "${AutomatedTestVcsRoot.id}"
            publisher = github {
                githubUrl = "https://api.github.com"
                authType = personalToken {
                    token = "credentialsJSON:0187b8ea-ad9f-4227-a350-7558d85cb876"
                }
            }
        }
    }
})

object Deploy : BuildType({
    name = "deploy"

    vcs {
        root(DeployVcsRoot)
    }

    steps {
        script {
            name = "Print Working Directory"
            id = "print_working_directory"
            scriptContent = "pwd"
        }
        dockerCommand {
            name = "Run Docker Build"
            id = "run_docker_build"
            commandType = build {
                source = file {
                    path = "Dockerfile.build"
                }
                namesAndTags = "autopublish-image"
            }
        }
        dockerCommand {
            name = "Run Docker Run"
            id = "run_docker_run"
            commandType = run {
                imageName = "autopublish-image"
                commandArgs = """
                  docker run --rm \
                  -e NPM_TOKEN=%env.NPM_TOKEN% \
                  -v $(pwd)/scripts/autoPublish:/app/scripts/autoPublish \
                  -v $(pwd)/apps/storybook-native/src/packages:/app/apps/storybook-native/src/packages \
                  -v $(pwd)/apps/storybook-web/src/packages:/app/apps/storybook-web/src/packages \
                  autopublish-image
                """.trimIndent()
        }
    }

    triggers {
        vcs {
        }
    }

    features {
        perfmon {
        }
    }
  }
})

object DeployVcsRoot : GitVcsRoot({
    name = "Deploy Vcs Root"
    url = "https://github.com/mobilestock/backend.git"
    branch = "refs/heads/main"
    branchSpec = "refs/heads/main"
    authMethod = password {
        userName = "Team City"
        password = "credentialsJSON:0187b8ea-ad9f-4227-a350-7558d85cb876"
    }
})

object AutomatedTestVcsRoot : GitVcsRoot({
    name = "Per PR commit"
    url = "https://github.com/mobilestock/backend.git"
    branch = "refs/heads/main"
    branchSpec = "refs/(pull/*)/merge"
    authMethod = password {
        userName = "Team City"
        password = "credentialsJSON:0187b8ea-ad9f-4227-a350-7558d85cb876"
    }
})
