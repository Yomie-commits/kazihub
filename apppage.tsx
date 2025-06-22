import Link from "next/link"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { MapPin, Star, Users, Briefcase, Globe, Shield } from "lucide-react"

export default function HomePage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-blue-50 to-white">
      {/* Header */}
      <header className="border-b bg-white/80 backdrop-blur-sm sticky top-0 z-50">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
              <span className="text-white font-bold text-sm">K</span>
            </div>
            <span className="text-xl font-bold text-gray-900">KaziHub</span>
          </div>
          <nav className="hidden md:flex items-center gap-6">
            <Link href="/freelancers" className="text-gray-600 hover:text-blue-600">
              Freelancers
            </Link>
            <Link href="/juakali" className="text-gray-600 hover:text-blue-600">
              Juakali Jobs
            </Link>
            <Link href="/uae-jobs" className="text-gray-600 hover:text-blue-600">
              UAE Jobs
            </Link>
            <Link href="/dashboard" className="text-gray-600 hover:text-blue-600">
              Dashboard
            </Link>
          </nav>
          <div className="flex items-center gap-2">
            <Link href="/auth/login">
              <Button variant="ghost">Sign In</Button>
            </Link>
            <Link href="/auth/register">
              <Button>Get Started</Button>
            </Link>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="container mx-auto px-4 py-16 text-center">
        <h1 className="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
          Find Work. Find Workers. <br />
          <span className="text-blue-600">Find Success.</span>
        </h1>
        <p className="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
          KaziHub connects skilled professionals with opportunities across freelance gigs, local juakali jobs, and
          verified UAE positions.
        </p>
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Link href="/auth/register">
            <Button size="lg" className="text-lg px-8">
              Find Work
            </Button>
          </Link>
          <Link href="/auth/register">
            <Button size="lg" variant="outline" className="text-lg px-8">
              Hire Talent
            </Button>
          </Link>
        </div>
      </section>

      {/* Stats Section */}
      <section className="container mx-auto px-4 py-12">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div>
            <div className="text-3xl font-bold text-blue-600">10K+</div>
            <div className="text-gray-600">Active Users</div>
          </div>
          <div>
            <div className="text-3xl font-bold text-blue-600">5K+</div>
            <div className="text-gray-600">Jobs Posted</div>
          </div>
          <div>
            <div className="text-3xl font-bold text-blue-600">98%</div>
            <div className="text-gray-600">Success Rate</div>
          </div>
          <div>
            <div className="text-3xl font-bold text-blue-600">24/7</div>
            <div className="text-gray-600">Support</div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="container mx-auto px-4 py-16">
        <h2 className="text-3xl font-bold text-center mb-12">Three Ways to Find Opportunities</h2>
        <div className="grid md:grid-cols-3 gap-8">
          {/* Freelancer Marketplace */}
          <Card className="relative overflow-hidden">
            <CardHeader>
              <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                <Briefcase className="w-6 h-6 text-purple-600" />
              </div>
              <CardTitle>Freelancer Marketplace</CardTitle>
              <CardDescription>
                Fiverr-style service listings with profiles, reviews, and secure payments
              </CardDescription>
            </CardHeader>
            <CardContent>
              <ul className="space-y-2 text-sm text-gray-600">
                <li>• Service listings & portfolios</li>
                <li>• Client reviews & ratings</li>
                <li>• Secure payment system</li>
                <li>• Direct messaging</li>
              </ul>
              <Link href="/freelancers">
                <Button className="w-full mt-4">Explore Services</Button>
              </Link>
            </CardContent>
          </Card>

          {/* Juakali Jobs */}
          <Card className="relative overflow-hidden">
            <CardHeader>
              <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                <MapPin className="w-6 h-6 text-green-600" />
              </div>
              <CardTitle>Juakali Jobs</CardTitle>
              <CardDescription>GPS-based local job postings with real-time worker availability</CardDescription>
            </CardHeader>
            <CardContent>
              <ul className="space-y-2 text-sm text-gray-600">
                <li>• Location-based matching</li>
                <li>• Real-time availability</li>
                <li>• Skill verification badges</li>
                <li>• Quick local connections</li>
              </ul>
              <Link href="/juakali">
                <Button className="w-full mt-4">Find Local Work</Button>
              </Link>
            </CardContent>
          </Card>

          {/* UAE Jobs */}
          <Card className="relative overflow-hidden">
            <CardHeader>
              <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                <Globe className="w-6 h-6 text-blue-600" />
              </div>
              <CardTitle>UAE Opportunities</CardTitle>
              <CardDescription>Verified international opportunities with visa support</CardDescription>
            </CardHeader>
            <CardContent>
              <ul className="space-y-2 text-sm text-gray-600">
                <li>• Verified employers</li>
                <li>• Visa assistance</li>
                <li>• Competitive salaries</li>
                <li>• Career advancement</li>
              </ul>
              <Link href="/uae-jobs">
                <Button className="w-full mt-4">View UAE Jobs</Button>
              </Link>
            </CardContent>
          </Card>
        </div>
      </section>

      {/* Trust & Safety */}
      <section className="bg-gray-50 py-16">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12">
            <Shield className="w-16 h-16 text-blue-600 mx-auto mb-4" />
            <h2 className="text-3xl font-bold mb-4">Trust & Safety First</h2>
            <p className="text-gray-600 max-w-2xl mx-auto">
              We verify all users, secure all payments, and provide 24/7 support to ensure safe and successful
              transactions for everyone.
            </p>
          </div>
          <div className="grid md:grid-cols-3 gap-8">
            <div className="text-center">
              <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <Shield className="w-6 h-6 text-blue-600" />
              </div>
              <h3 className="font-semibold mb-2">Verified Profiles</h3>
              <p className="text-gray-600 text-sm">All users go through identity verification</p>
            </div>
            <div className="text-center">
              <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <Users className="w-6 h-6 text-blue-600" />
              </div>
              <h3 className="font-semibold mb-2">Secure Payments</h3>
              <p className="text-gray-600 text-sm">Escrow system protects both parties</p>
            </div>
            <div className="text-center">
              <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                <Star className="w-6 h-6 text-blue-600" />
              </div>
              <h3 className="font-semibold mb-2">24/7 Support</h3>
              <p className="text-gray-600 text-sm">Always here to help resolve issues</p>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="container mx-auto px-4 py-16 text-center">
        <h2 className="text-3xl font-bold mb-4">Ready to Get Started?</h2>
        <p className="text-gray-600 mb-8 max-w-xl mx-auto">
          Join thousands of professionals who trust KaziHub for their career growth
        </p>
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Link href="/auth/register">
            <Button size="lg">Create Account</Button>
          </Link>
          <Link href="/auth/login">
            <Button size="lg" variant="outline">
              Sign In
            </Button>
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="container mx-auto px-4">
          <div className="grid md:grid-cols-4 gap-8">
            <div>
              <div className="flex items-center gap-2 mb-4">
                <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                  <span className="text-white font-bold text-sm">K</span>
                </div>
                <span className="text-xl font-bold">KaziHub</span>
              </div>
              <p className="text-gray-400 text-sm">
                Connecting talent with opportunities across Africa and the Middle East.
              </p>
            </div>
            <div>
              <h3 className="font-semibold mb-4">For Job Seekers</h3>
              <ul className="space-y-2 text-sm text-gray-400">
                <li>
                  <Link href="/freelancers">Browse Services</Link>
                </li>
                <li>
                  <Link href="/juakali">Local Jobs</Link>
                </li>
                <li>
                  <Link href="/uae-jobs">UAE Opportunities</Link>
                </li>
              </ul>
            </div>
            <div>
              <h3 className="font-semibold mb-4">For Employers</h3>
              <ul className="space-y-2 text-sm text-gray-400">
                <li>
                  <Link href="/post-job">Post a Job</Link>
                </li>
                <li>
                  <Link href="/hire">Hire Freelancers</Link>
                </li>
                <li>
                  <Link href="/enterprise">Enterprise</Link>
                </li>
              </ul>
            </div>
            <div>
              <h3 className="font-semibold mb-4">Support</h3>
              <ul className="space-y-2 text-sm text-gray-400">
                <li>
                  <Link href="/help">Help Center</Link>
                </li>
                <li>
                  <Link href="/contact">Contact Us</Link>
                </li>
                <li>
                  <Link href="/safety">Safety</Link>
                </li>
              </ul>
            </div>
          </div>
          <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
            <p>&copy; 2024 KaziHub. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  )
}