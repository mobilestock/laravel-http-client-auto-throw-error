import jetbrains.buildServer.configs.kotlin.*
import jetbrains.buildServer.configs.kotlin.buildFeatures.PullRequests
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

version = "2023.11"

project {

    vcsRoot(HttpsGithubComMobilestockBackendGitRefsHeadsMain1)

    buildType(Build)
    buildType(Deploy)
}

object Build : BuildType({
    name = "automated test"

    vcs {
        root(DslContext.settingsRoot)
    }

    steps {
        script {
            name = "[test] build image"
            id = "test_automation"
            scriptContent = "docker build -t backend_pdo-cast-adm-api-integration ./shared/pdo-cast"
            formatStderrAsError = true
        }
        script {
            name = "[test] pdo-cast"
            id = "test_pdo_cast"
            enabled = false
            scriptContent = "docker compose -f ./docker-compose.test.yml run --rm pdo-cast-adm-api-integration"
            formatStderrAsError = true
        }
        script {
            name = "[test] adm-api"
            id = "test_adm_api"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --rm adm-api"
            formatStderrAsError = true
        }
        script {
            name = "[test] load-balancer"
            id = "test_load_balancer"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --rm load-balancer"
            formatStderrAsError = true
        }
        script {
            name = "[test] lookpay-api"
            id = "test_lookpay_api"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --build --rm lookpay-api"
            formatStderrAsError = true
        }
        script {
            name = "[test] wc-lookpay-credit-card"
            id = "test_wc_lookpay_credit_card"
            scriptContent = "docker compose -f ./docker-compose.test.yml run --rm wc-lookpay-credit-card"
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
            vcsRootExtId = "${DslContext.settingsRoot.id}"
            provider = github {
                authType = token {
                    token = "credentialsJSON:0187b8ea-ad9f-4227-a350-7558d85cb876"
                }
                filterTargetBranch = "+:refs/heads/main"
                filterAuthorRole = PullRequests.GitHubRoleFilter.MEMBER
            }
        }
        commitStatusPublisher {
            vcsRootExtId = "${DslContext.settingsRoot.id}"
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

    params {
        param("env.PORTAINER_STACK_WEBHOOK", "testeste")
        param("env.AWS_SECRET_ACCESS_KEY", "b4+KAjLLtnkPr2brn9niOR68WorHKOEqPJOq1ufy")
        param("env.CONTAINER_REGISTRY", "192.168.0.97:32768/")
        param("env.AWS_ACCESS_KEY_ID", "AKIASHWRDYSYK5LE6CGR")
    }

    vcs {
        root(HttpsGithubComMobilestockBackendGitRefsHeadsMain1)
    }

    steps {
        script {
            name = "ECR Login"
            id = "login"
            scriptContent = "docker run --rm -it -e AWS_ACCESS_KEY_ID=%env.AWS_ACCESS_KEY_ID% -e AWS_SECRET_ACCESS_KEY=%env.AWS_SECRET_ACCESS_KEY% amazon/aws-cli ecr get-login-password --region sa-east-1 | docker login --username AWS --password-stdin %env.CONTAINER_REGISTRY%"
        }
        script {
            name = "[build] lib"
            id = "build_1"
            enabled = false
            scriptContent = "docker build -t backend_pdo-cast-adm-api-integration ./shared/pdo-cast"
        }
        dockerCommand {
            name = "[build] adm-api"
            id = "adm_api"
            enabled = false
            commandType = build {
                source = file {
                    path = "apps/adm-api/Dockerfile"
                }
                namesAndTags = """
                    adm-api:latest
                    %env.CONTAINER_REGISTRY%adm-api:latest
                """.trimIndent()
            }
        }
        dockerCommand {
            name = "[push] adm-api"
            id = "push"
            enabled = false
            commandType = push {
                namesAndTags = "%env.CONTAINER_REGISTRY%adm-api"
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
            name = "[build] load-balancer"
            id = "build_load_balancer"
            commandType = build {
                source = file {
                    path = "apps/load-balancer/Dockerfile"
                }
                namesAndTags = "%env.CONTAINER_REGISTRY%load-balancer:latest"
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
            name = "[Deploy] Deploy to Portainer"
            id = "deploy"
            scriptContent = "Invoke-WebRequest -Uri %env.PORTAINER_STACK_WEBHOOK% -Method POST"
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
    }
})

object HttpsGithubComMobilestockBackendGitRefsHeadsMain1 : GitVcsRoot({
    name = "https://github.com/mobilestock/backend.git#refs/heads/main (1)"
    url = "https://github.com/mobilestock/backend.git"
    branch = "refs/heads/main"
    branchSpec = "refs/heads/main"
    authMethod = password {
        userName = "Team City"
        password = "credentialsJSON:0187b8ea-ad9f-4227-a350-7558d85cb876"
    }
})
