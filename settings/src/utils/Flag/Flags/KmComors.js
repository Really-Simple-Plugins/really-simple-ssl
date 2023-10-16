import * as React from "react";
const SvgKmComors = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <path
      fill="#5196ED"
      fillRule="evenodd"
      d="M0 0v12h16V0H0Z"
      clipRule="evenodd"
    />
    <mask
      id="KM_-_Comors_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path
        fill="#fff"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#KM_-_Comors_svg__a)">
      <path fill="#AF0100" d="M0 6v3h16V6H0Z" />
      <path fill="#F7FCFF" d="M0 3v3h16V3H0Z" />
      <path fill="#FBCD17" d="M0 0v3h16V0H0Z" />
      <path fill="#5EAA22" d="m0 0 8 6-8 6V0Z" />
      <path
        fill="#F7FCFF"
        d="M3.152 8.728S1.361 8.06 1.424 5.972c.063-2.088 1.888-2.497 1.888-2.497C2.672 2.97.387 3.56.314 5.972.24 8.383 2.47 8.92 3.152 8.728Zm.095-3.585.068-.393-.286-.278.395-.057.176-.358.176.358.395.057-.286.278.068.393-.353-.186-.353.186Zm.068.607-.068.393.353-.186.353.186-.068-.393.286-.278-.395-.057-.176-.358-.176.358-.395.057.286.278Zm-.068 1.393.068-.393-.286-.278.395-.057.176-.358.176.358.395.057-.286.278.068.393-.353-.186-.353.186Zm0 1 .068-.393-.286-.278.395-.057.176-.358.176.358.395.057-.286.278.068.393-.353-.186-.353.186Z"
      />
    </g>
  </svg>
);
export default SvgKmComors;
