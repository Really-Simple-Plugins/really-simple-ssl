import * as React from "react";
const SvgZaSouthAfrica = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="ZA_-_South_Africa_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#ZA_-_South_Africa_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v4h16V0H0Z"
        clipRule="evenodd"
      />
      <path
        fill="#3D58DB"
        fillRule="evenodd"
        d="M0 8v4h16V8H0Z"
        clipRule="evenodd"
      />
      <mask
        id="ZA_-_South_Africa_svg__b"
        width={18}
        height={20}
        x={-1}
        y={-4}
        fill="#000"
        maskUnits="userSpaceOnUse"
      >
        <path fill="#fff" d="M-1-4h18v20H-1z" />
        <path
          fillRule="evenodd"
          d="M7.714 5 0-1v14l7.714-6H16V5H7.714Z"
          clipRule="evenodd"
        />
      </mask>
      <path
        fill="#5EAA22"
        fillRule="evenodd"
        d="M7.714 5 0-1v14l7.714-6H16V5H7.714Z"
        clipRule="evenodd"
      />
      <path
        fill="#F7FCFF"
        d="m0-1 .614-.79L-1-3.044V-1h1Zm7.714 6-.614.79.271.21h.343V5ZM0 13h-1v2.045l1.614-1.256L0 13Zm7.714-6V6h-.343l-.27.21.613.79ZM16 7v1h1V7h-1Zm0-2h1V4h-1v1ZM-.614-.21l7.714 6 1.228-1.58-7.714-6L-.614-.21ZM1 13V-1h-2v14h2Zm6.1-6.79-7.714 6 1.228 1.58 7.714-6L7.1 6.21ZM16 6H7.714v2H16V6Zm-1-1v2h2V5h-2ZM7.714 6H16V4H7.714v2Z"
        mask="url(#ZA_-_South_Africa_svg__b)"
      />
      <path
        fill="#272727"
        stroke="#FECA00"
        d="M.3 2.6-.5 2v8l.8-.6 4-3 .533-.4-.533-.4-4-3Z"
      />
    </g>
  </svg>
);
export default SvgZaSouthAfrica;
