% Generated by roxygen2: do not edit by hand
% Please edit documentation in R/autoburnin.R
\name{autoburnin}
\alias{autoburnin}
\title{Automatically calculate and apply burnin value}
\usage{
autoburnin(jags_out, return.burnin = FALSE, ...)
}
\arguments{
\item{jags_out}{List of MCMC sample matrices or `mcmc.list` object}

\item{return.burnin}{Logical. If `TRUE`, return burnin value in addition to samples (as list).}

\item{...}{Additional arguments for `getBurnin`, `gelman.plot`, and `gelman.diag`
Default = FALSE.}
}
\examples{
     library(coda)
     data(line)
     line_burned <- autoburnin(line, threshold = 1.05, return.burnin=FALSE)
}
\author{
Michael Dietze, Alexey Shiklomanov
}

