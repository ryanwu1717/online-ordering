import { Outlet, Link } from "react-router-dom";
import Home from "./Home";

const Layout = () => {
  return (
    <div>
      <Home>    
      </Home>
      <Outlet />
    </div>
  )
};

export default Layout;
