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
            name = "ECR Login"
            id = "login"
            scriptContent = "docker run --rm -e AWS_ACCESS_KEY_ID=%env.AWS_ACCESS_KEY_ID% -e AWS_SECRET_ACCESS_KEY=%env.AWS_SECRET_ACCESS_KEY% amazon/aws-cli ecr get-login-password --region sa-east-1 | docker login --username AWS --password-stdin %env.CONTAINER_REGISTRY%"
        }
        script {
            name = "[build] backend-shared"
            id = "build_1"
            scriptContent = "docker build --platform linux/amd64 -t backend-shared:latest ./shared"
        }
        dockerCommand {
            name = "[build] adm-api"
            id = "adm_api"
            commandType = build {
                source = file {
                    path = "apps/adm-api/Dockerfile"
                }
                namesAndTags = "%env.CONTAINER_REGISTRY%adm-api:latest"
                commandArgs = "--platform linux/amd64"
            }
        }
        dockerCommand {
            name = "[push] adm-api"
            id = "push"
            commandType = push {
                namesAndTags = "%env.CONTAINER_REGISTRY%adm-api"
            }
        }
        dockerCommand {
            name = "[build] adm-cli"
            id = "build_adm-cli"
            commandType = build {
                source = file {
                    path = "apps/adm-api/Dockerfile.cli"
                }
                namesAndTags = "%env.CONTAINER_REGISTRY%adm-cli:latest"
                commandArgs = "--platform linux/amd64"
            }
        }
        dockerCommand {
            name = "[push] adm-cli"
            id = "push_adm-cli"
            commandType = push {
                namesAndTags = "%env.CONTAINER_REGISTRY%adm-cli"
            }
        }
        dockerCommand {
            name = "[build] lookpay-api"
            id = "build_lookpay_api"
            commandType = build {
                source = file {
                    path = "apps/lookpay-api/Dockerfile"
                }
                namesAndTags = """
                    lookpay-api:latest
                    %env.CONTAINER_REGISTRY%lookpay-api:latest
                """.trimIndent()
                commandArgs = "--platform linux/amd64"
            }
        }
        dockerCommand {
            name = "[push] lookpay-api"
            id = "push_lookpay_api"
            commandType = push {
                namesAndTags = "%env.CONTAINER_REGISTRY%lookpay-api:latest"
                removeImageAfterPush = false
            }
        }
        dockerCommand {
            name = "[build] med-api"
            id = "build_med_api"
            commandType = build {
                source = file {
                    path = "apps/med-api/Dockerfile"
                }
                namesAndTags = """
                    med-api:latest
                    %env.CONTAINER_REGISTRY%med-api:latest
                """.trimIndent()
                commandArgs = "--platform linux/amd64"
            }
        }
        dockerCommand {
            name = "[push] med-api"
            id = "push_med_api"
            commandType = push {
                namesAndTags = "%env.CONTAINER_REGISTRY%med-api:latest"
                removeImageAfterPush = false
            }
        }
        dockerCommand {
            name = "[build] load-balancer"
            id = "build_load_balancer"
            commandType = build {
                source = file {
                    path = "apps/load-balancer/Dockerfile"
                }
                namesAndTags = "%env.CONTAINER_REGISTRY%load-balancer:latest"
                commandArgs = "--platform linux/amd64"
            }
        }
        dockerCommand {
            name = "[push] load-balancer"
            id = "push_load_balancer"
            commandType = push {
                namesAndTags = "%env.CONTAINER_REGISTRY%load-balancer:latest"
            }
        }
        script {
            name = "[Deploy] stack backend"
            id = "deploy"
            scriptContent = "curl -X POST https://portainer.lookpay.com.br/api/stacks/webhooks/8b7f771f-1012-4d64-bc7f-9b9a68c911c8"
            formatStderrAsError = true
        }
        script {
            name = "[Deploy] adm-cli-sqs"
            id = "deploy-service-1"
            scriptContent = "curl -X POST https://portainer.lookpay.com.br/api/webhooks/22ac410d-1418-48eb-b580-a0021b3a1ae2"
            formatStderrAsError = true
        }
        script {
            name = "[Deploy] adm-cli-job-atualiza-sicoob"
            id = "deploy-service-2"
            scriptContent = "curl -X POST https://portainer.lookpay.com.br/api/webhooks/e1d5b840-1e30-4abf-8335-f0887a4e8434"
            formatStderrAsError = true
        }
        script {
            name = "[Deploy] Notification"
            id = "notification"
            executionMode = BuildStep.ExecutionMode.RUN_ONLY_ON_FAILURE
            scriptContent = """
                #!/bin/bash

                MESSAGE="#TEAM_CITY_BUILD_ERROR\n\nO build %system.build.number% do projeto %system.teamcity.projectName% falhou ao tentar fazer o deploy. Detalhes: %env.BUILD_URL%"

                curl -X POST -H 'Content-Type: application/json' -d "{\"chat_id\": \"%env.TELEGRAM_CHAT_ID%\", \"text\": \"${'$'}MESSAGE\", \"disable_notification\": true}" https://api.telegram.org/bot%env.TELEGRAM_BOT_TOKEN%/sendMessage
            """.trimIndent()
        }
        script {
            name = "Run AutoPublish Script"
            id = "run_auto_publish"
            scriptContent = """
                docker run --rm \
                -e NPM_TOKEN=%env.NPM_TOKEN% \
                -v $(pwd)/scripts/autoPublish:/app/scripts/autoPublish \
                -v $(pwd)/apps/storybook-native/src/packages:/app/apps/storybook-native/src/packages \
                -v $(pwd)/apps/storybook-web/src/packages:/app/apps/storybook-web/src/packages \
                -w /app/scripts/autoPublish node:18-alpine node index.js
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
